<?php

namespace App\Services;

use App\Models\Quest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class QuestService extends CrudService
{
    public function __construct(private ProcedureService $procedures) {}

    protected function model(): string
    {
        return Quest::class;
    }

    protected function relacionamentos(): array
    {
        return ['aventureiro', 'dungeon'];
    }

    public function criar(array $dados): Model
    {
        return DB::transaction(function () use ($dados) {
            $progresso = $this->procedures->registrarQuest(
                (int) $dados['aventureiro_id'], (int) $dados['dungeon_id'],
                $dados['data_quest'] ?? now()->toDateString(), (bool) $dados['sucesso'],
            );

            $quest = $this->query()->findOrFail($progresso->quest_id);
            $quest->wasRecentlyCreated = true;

            return $quest;
        }, 3);
    }

    public function atualizar(Model $registro, array $dados): Model
    {
        return DB::transaction(function () use ($registro, $dados) {
            $registro = $registro->newQuery()->whereKey($registro->id)->lockForUpdate()->firstOrFail();
            $anterior = $registro->aventureiro_id;
            $registro = parent::atualizar($registro, $dados);
            $this->procedures->recalcularNiveis([$anterior, $registro->aventureiro_id]);

            return $registro->refresh()->load($this->relacionamentos());
        }, 3);
    }

    public function excluir(Model $registro): void
    {
        DB::transaction(function () use ($registro) {
            $id = $registro->aventureiro_id;
            parent::excluir($registro);
            $this->procedures->recalcularNiveis([$id]);
        }, 3);
    }
}
