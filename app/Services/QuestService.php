<?php

namespace App\Services;

use App\Models\Quest;
use Illuminate\Database\Eloquent\Model;

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
        $dados['data_exploracao'] ??= now()->toDateString();

        return parent::criar($dados);
    }
}
