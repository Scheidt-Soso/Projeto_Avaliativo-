<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Dungeon;
use App\Models\Guilda;
use App\Services\PerfilService;
use App\Services\ProcedureService;
use App\Services\QuestService;
use App\Services\SubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function __construct(private PerfilService $perfil, private ProcedureService $procedures, private QuestService $quests, private SubmissionService $submissoes) {}

    public function show(Request $request): View
    {
        $filtros = $request->validate(['dungeon' => ['nullable', 'integer', Rule::exists(Dungeon::class, 'id')]]);
        return view('jogador.perfil', $this->perfil->dados($request->user()) + ['dungeonSelecionada' => $filtros['dungeon'] ?? null]);
    }

    public function vincularGuilda(Request $request): RedirectResponse
    {
        $dados = $request->validate(['guilda_id' => ['required', 'integer', Rule::exists(Guilda::class, 'id')]]);
        $this->procedures->vincularAventureiroGuilda($request->user()->id, $dados['guilda_id']);

        return to_route('guildas.dashboard', ['guilda' => $dados['guilda_id']])->with('sucesso', 'Sua bandeira agora é a desta guilda. Vínculo atualizado.');
    }

    public function registrarQuest(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'dungeon_id' => ['required', 'integer', Rule::exists(Dungeon::class, 'id')],
            'data_quest' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'sucesso' => ['required', 'boolean'], 'submission_token' => ['required', 'uuid'],
        ]);
        $token = $dados['submission_token'];
        unset($dados['submission_token']);
        $dados['aventureiro_id'] = $request->user()->id;
        $this->submissoes->criar('quests', $token, fn () => $this->quests->criar($dados));
        $nivel = $request->user()->fresh()->nivel;

        return to_route('perfil.show')->with('sucesso', "Quest registrada. Seu nível atual é {$nivel}.");
    }
}
