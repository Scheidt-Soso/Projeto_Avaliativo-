<?php

namespace Tests\Support;

use App\Models\Aventureiro;
use App\Models\Quest;
use App\Services\ProcedureService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Test double used only by the SQLite HTTP suite. Native SQL is tested separately. */
class FakeProcedures extends ProcedureService
{
    public function registrarQuest(int $aventureiroId, int $dungeonId, string $dataQuest, bool $sucesso): object
    {
        $quest = Quest::create(['aventureiro_id' => $aventureiroId, 'dungeon_id' => $dungeonId, 'data_quest' => $dataQuest, 'sucesso' => $sucesso]);
        $this->recalcularNiveis([$aventureiroId]);
        return (object) ['quest_id' => $quest->id, 'xp_total' => $this->xp($aventureiroId), 'nivel_atual' => Aventureiro::findOrFail($aventureiroId)->nivel];
    }

    public function vincularAventureiroGuilda(int $aventureiroId, int $guildaId): void
    {
        $aventureiro = Aventureiro::findOrFail($aventureiroId);
        $guilda = \App\Models\Guilda::findOrFail($guildaId);
        if ($aventureiro->nivel < $guilda->nivel_minimo) throw ValidationException::withMessages(['guilda_id' => 'Nível insuficiente para entrar nesta guilda.']);
        $aventureiro->update(['guilda_id' => $guildaId]);
    }

    public function recalcularNiveis(array $aventureiros): void
    {
        foreach (array_unique($aventureiros) as $id) {
            Aventureiro::whereKey($id)->update(['nivel' => min(1 + intdiv($this->xp($id), 1000), 100)]);
        }
    }

    private function xp(int $id): int
    {
        return (int) DB::table('rpg_trabalho_bd.quests as q')->join('rpg_trabalho_bd.dungeons as d', 'd.id', '=', 'q.dungeon_id')
            ->where('q.aventureiro_id', $id)->where('q.sucesso', true)->sum('d.xp_recompensa');
    }
}
