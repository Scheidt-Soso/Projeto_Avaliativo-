<?php

namespace App\Services;

use App\Models\Aventureiro;
use Illuminate\Support\Facades\DB;

class AventureiroService extends CrudService
{
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
        DB::statement(
            'CALL rpg_trabalho_bd.pr_vincular_aventureiro_guilda(?, ?)',
            [$aventureiroId, $guildaId]
        );
    }
}
