<?php

namespace App\Services;

use App\Models\Aventureiro;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class AventureiroService extends CrudService
{
    public function __construct(private ProcedureService $procedures) {}

    public function criar(array $dados): Model
    {
        return DB::transaction(function () use ($dados) {
            $guildaId = $dados['guilda_id'] ?? null;
            $dados['guilda_id'] = null;
            $dados['nivel'] ??= 1;
            $dados['role'] ??= 'jogador';
            if (! empty($dados['senha'])) $dados['senha'] = Hash::make($dados['senha']);
            $aventureiro = parent::criar($dados);
            if ($guildaId !== null) $this->procedures->vincularAventureiroGuilda($aventureiro->id, (int) $guildaId);

            return $aventureiro->refresh()->load('guilda');
        }, 3);
    }

    public function atualizar(Model $registro, array $dados): Model
    {
        return DB::transaction(function () use ($registro, $dados) {
            $alterarGuilda = array_key_exists('guilda_id', $dados);
            $guildaId = $dados['guilda_id'] ?? null;
            unset($dados['guilda_id'], $dados['senha']);
            $registro = parent::atualizar($registro, $dados);
            if ($alterarGuilda) {
                if ($guildaId !== null) $this->procedures->vincularAventureiroGuilda($registro->id, (int) $guildaId);
                else $registro->update(['guilda_id' => null]);
            }

            return $registro->refresh()->load('guilda');
        }, 3);
    }

    public function excluir(Model $registro): void
    {
        DB::transaction(function () use ($registro) {
            parent::excluir($registro);
            $this->procedures->recalcularNiveis([]);
        }, 3);
    }
    public function listarParaSelecao(string $busca = '', string $classe = ''): LengthAwarePaginator
    {
        return $this->query()
            ->when($busca !== '', fn ($query) => $query->whereRaw("LOWER(nickname) LIKE ? ESCAPE '!'", [
                '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($busca)).'%',
            ]))
            ->when($classe !== '', fn ($query) => $query->where('classe', $classe))
            ->orderBy('id')->paginate(12)->withQueryString();
    }

    public function classesDisponiveis(): Collection
    {
        return Aventureiro::query()->distinct()->orderBy('classe')->pluck('classe');
    }

    public function encontrarParaSelecao(int $id): Aventureiro
    {
        return $this->query()->findOrFail($id);
    }

    public function encontrarSelecionado(int $id): ?Aventureiro
    {
        return $this->query()->find($id);
    }

    protected function model(): string
    {
        return Aventureiro::class;
    }

    protected function relacionamentos(): array
    {
        return ['guilda'];
    }

    public function vincularAventureiroGuilda($aventureiroId, $guildaId): void
    {
        $this->procedures->vincularAventureiroGuilda((int) $aventureiroId, (int) $guildaId);
    }

    
}
