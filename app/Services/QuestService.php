<?php

namespace App\Services;

use App\Models\Quest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class QuestService extends CrudService
{
    protected function model(): string
    {
        return Quest::class;
    }

    protected function relacionamentos(): array
    {
        return ['aventureiro', 'dungeon'];
    }

    /**
     * O jogo manda só o resultado da run; a data é a de hoje se não vier.
     * (XP e nível do aventureiro serão atualizados pelo banco, via trigger.)
     */
    public function criar(array $dados): Model
    {
        $dados['data_quest'] ??= now()->toDateString();

        return parent::criar($dados);
    }

    public function registrarQuest(
        string $dataQuest,
        int $aventureiroId,
        int $dungeonId,
        string $resultado
    ): array {
        $sucesso = $resultado === 'sucesso';

    $resultadoProcedure = DB::select(
        'CALL rpg_trabalho_bd.pr_registrar_quest(?, ?, ?, ?, NULL, NULL)',
        [
            $aventureiroId,
            $dungeonId,
            $dataQuest,
            $sucesso
        ]
    );

    return (array) $resultadoProcedure[0];
}
}
