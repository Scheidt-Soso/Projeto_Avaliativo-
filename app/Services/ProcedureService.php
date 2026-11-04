<?php

namespace App\Services;

use App\Models\Aventureiro;
use App\Models\Guilda;
use Illuminate\Support\Facades\DB;

class ProcedureService
{
    public function registrarQuest(int $aventureiroId, int $dungeonId, string $dataQuest, bool $sucesso): object
    {
        return DB::transaction(function () use ($aventureiroId, $dungeonId, $dataQuest, $sucesso) {
            $this->configurarSchema();
            Aventureiro::query()->whereKey($aventureiroId)->lockForUpdate()->firstOrFail();
            $progresso = DB::selectOne(
                'CALL rpg_trabalho_bd.pr_registrar_quest(?, ?, ?, ?, NULL, NULL)',
                [$aventureiroId, $dungeonId, $dataQuest, $sucesso]
            );
            $progresso->quest_id = (int) DB::selectOne("SELECT currval(pg_get_serial_sequence('rpg_trabalho_bd.quests', 'id')) AS id")->id;
            $this->atualizarRanking();

            return $progresso;
        }, 3);
    }

    public function vincularAventureiroGuilda(int $aventureiroId, int $guildaId): void
    {
        DB::transaction(function () use ($aventureiroId, $guildaId) {
            $this->configurarSchema();
            Guilda::query()->whereKey($guildaId)->lockForUpdate()->firstOrFail();
            Aventureiro::query()->whereKey($aventureiroId)->lockForUpdate()->firstOrFail();
            DB::statement('CALL rpg_trabalho_bd.pr_vincular_aventureiro_guilda(?, ?)', [$aventureiroId, $guildaId]);
        }, 3);
    }

    public function recalcularNiveis(array $aventureiros): void
    {
        DB::transaction(function () use ($aventureiros) {
            $this->configurarSchema();
            $aventureiros = array_unique($aventureiros);
            sort($aventureiros);
            foreach ($aventureiros as $id) {
                if (Aventureiro::query()->whereKey($id)->lockForUpdate()->first(['id'])) {
                    DB::selectOne('SELECT rpg_trabalho_bd.fn_atualizar_nivel_aventureiro(?) AS nivel', [$id]);
                }
            }
            $this->atualizarRanking();
        }, 3);
    }

    public function progresso(int $aventureiroId): object
    {
        return DB::transaction(function () use ($aventureiroId) {
            $this->configurarSchema();
            return DB::selectOne('SELECT xp_total, rpg_trabalho_bd.fn_nivel_por_xp(xp_total) AS nivel_calculado FROM (SELECT rpg_trabalho_bd.fn_total_xp(?) AS xp_total) AS progresso', [$aventureiroId]);
        });
    }

    private function configurarSchema(): void
    {
        DB::statement('SET LOCAL search_path TO rpg_trabalho_bd, public');
    }

    private function atualizarRanking(): void
    {
        DB::statement('REFRESH MATERIALIZED VIEW rpg_trabalho_bd.vw_matheusayres_ranking_xp');
    }
}
