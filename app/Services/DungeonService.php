<?php

namespace App\Services;

use App\Models\Dungeon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DungeonService extends CrudService
{
    public function __construct(private ProcedureService $procedures) {}

    public function excluir(Model $registro): void
    {
        DB::transaction(function () use ($registro) {
            $aventureiros = $registro->quests()->pluck('aventureiro_id')->all();
            parent::excluir($registro);
            $this->procedures->recalcularNiveis($aventureiros);
        }, 3);
    }
    protected function model(): string
    {
        return Dungeon::class;
    }
}
