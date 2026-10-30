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
     * O CRUD registra a quest com a data de hoje quando ela não é informada.
     * A progressão de nível pertence à procedure de expedição, não a este CRUD.
     */
    public function criar(array $dados): Model
    {
        $dados['data_quest'] ??= now()->toDateString();

        return parent::criar($dados);
    }
}
