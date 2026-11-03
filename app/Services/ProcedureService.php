<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ProcedureService
{
    public function registrarQuest(int $aventureiroId, int $dungeonId, string $dataQuest, bool $sucesso): object
    {
        return DB::selectOne(
            'CALL rpg_trabalho_bd.pr_registrar_quest(?, ?, ?, ?, NULL, NULL)',
            [$aventureiroId, $dungeonId, $dataQuest, $sucesso]
        );
    }

    public function vincularAventureiroGuilda(int $aventureiroId, int $guildaId): void
    {
        DB::statement(
            'CALL rpg_trabalho_bd.pr_vincular_aventureiro_guilda(?, ?)',
            [$aventureiroId, $guildaId]
        );
    }
}
