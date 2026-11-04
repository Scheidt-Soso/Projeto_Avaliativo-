<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Aventureiro;
use App\Services\CatalogoService;
use App\Services\CrudService;
use App\Services\SubmissionService;
use App\Support\RecordRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

abstract class CatalogoController extends Controller
{
    protected string $recurso;
    protected CrudService $service;

    public function __construct(private CatalogoService $catalogo, private SubmissionService $submissoes)
    {
        $this->service = app(match ($this->recurso) {
            'guildas' => \App\Services\GuildaService::class,
            'aventureiros' => \App\Services\AventureiroService::class,
            'dungeons' => \App\Services\DungeonService::class,
            'quests' => \App\Services\QuestService::class,
        });
    }

    public function index(Request $request): View
    {
        $extras = match ($this->recurso) {
            'aventureiros' => ['guilda_id' => ['nullable', 'integer'], 'classe' => ['nullable', 'string', 'max:50']],
            'dungeons' => ['dificuldade' => ['nullable', 'string', 'max:20']],
            'quests' => ['aventureiro_id' => ['nullable', 'integer'], 'dungeon_id' => ['nullable', 'integer'], 'sucesso' => ['nullable', 'boolean']],
            default => [],
        };
        $filtros = $request->validate(['busca' => ['nullable', 'string', 'max:100']] + $extras);

        return view('cadastros.index', $this->dados() + [
            'registros' => $this->catalogo->listar($this->recurso, $filtros), 'filtros' => $filtros,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('gerenciar-cadastros');
        $defaults = array_map(fn ($campo) => $campo['default'] ?? null, config("cadastros.{$this->recurso}.campos"));
        if ($this->recurso === 'quests') {
            $defaults['data_quest'] = now()->toDateString();
            foreach (['aventureiro_id', 'dungeon_id'] as $campo) $defaults[$campo] = $request->integer($campo) ?: null;
        }

        return view('cadastros.form', $this->dados() + ['registro' => null, 'valores' => $defaults]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('gerenciar-cadastros');
        $rules = RecordRules::for($this->recurso);
        if ($this->recurso === 'aventureiros' && $request->filled('senha')) $rules['senha'] = RecordRules::password();
        $dados = $request->validate($rules + ['submission_token' => ['required', 'uuid']]);
        $token = $dados['submission_token'];
        unset($dados['submission_token']);
        $id = $this->submissoes->criar($this->recurso, $token, fn () => $this->service->criar($dados));

        return to_route('cadastros.'.$this->recurso.'.show', $id)->with('sucesso', 'Registro criado com sucesso.');
    }

    public function show(int $registro): View
    {
        $registro = $this->catalogo->encontrar($this->recurso, $registro);
        return view('cadastros.show', $this->dados() + [
            'registro' => $registro, 'historico' => $this->catalogo->historico($registro),
            'impacto' => $this->catalogo->impacto($registro),
        ]);
    }

    public function edit(int $registro): View
    {
        Gate::authorize('gerenciar-cadastros');
        $registro = $this->catalogo->encontrar($this->recurso, $registro);
        return view('cadastros.form', $this->dados() + ['registro' => $registro, 'valores' => $registro->getAttributes(), 'xpProtegido' => $this->recurso === 'dungeons' && $registro->quests()->exists()]);
    }

    public function update(Request $request, int $registro): RedirectResponse
    {
        Gate::authorize('gerenciar-cadastros');
        $registro = $this->catalogo->encontrar($this->recurso, $registro);
        $dados = $request->validate(RecordRules::for($this->recurso, $registro->id));
        if ($registro instanceof Aventureiro && $registro->id === $request->user()->id && $dados['role'] !== 'admin') {
            throw ValidationException::withMessages(['role' => 'Mantenha o acesso administrativo da sua própria conta.']);
        }
        $this->service->atualizar($registro, $dados);

        return to_route('cadastros.'.$this->recurso.'.show', $registro->id)->with('sucesso', 'Registro atualizado com sucesso.');
    }

    public function destroy(Request $request, int $registro): RedirectResponse
    {
        Gate::authorize('gerenciar-cadastros');
        $registro = $this->catalogo->encontrar($this->recurso, $registro);
        if ($registro instanceof Aventureiro && $registro->id === $request->user()->id) {
            throw ValidationException::withMessages(['confirmacao' => 'Sua conta não pode ser excluída durante esta sessão.']);
        }
        $confirmacao = $request->validate(['confirmacao' => ['required', 'string']]);
        $nome = (string) ($registro->nome ?? $registro->nickname ?? '#'.$registro->id);
        if ($confirmacao['confirmacao'] !== $nome) {
            throw ValidationException::withMessages(['confirmacao' => 'Digite o nome do registro exatamente como aparece na confirmação.']);
        }
        $this->service->excluir($registro);

        return to_route('cadastros.'.$this->recurso.'.index')->with('sucesso', 'Registro excluído com sucesso.');
    }

    private function dados(): array
    {
        return ['recurso' => $this->recurso, 'meta' => config("cadastros.{$this->recurso}"), 'opcoes' => $this->catalogo->opcoes()];
    }
}
