<?php

namespace App\Services;

use App\Models\Guilda;
use App\Models\Dungeon;
use App\Models\Quest;
use Illuminate\Validation\ValidationException;

class GuildDashboardService
{
    public function sortearDungeon(): Dungeon
    {
        return Dungeon::query()->inRandomOrder()->first()
            ?? throw ValidationException::withMessages(['dungeon' => 'Nenhuma dungeon foi cadastrada para o sorteio.']);
    }

    public function dashboard(?int $guildaId = null): array
    {
        $guildas = Guilda::query()->select(['id', 'nome', 'nivel_minimo', 'data_fundacao'])->withCount('aventureiros')->orderBy('id')->get();
        $guilda = $guildaId ? $guildas->firstWhere('id', $guildaId) : $guildas->first();
        if (! $guilda) {
            return ['guildas' => $guildas, 'guilda' => null, 'resumo' => ['membros' => 0, 'quests' => 0, 'sucessos' => 0, 'xp' => 0], 'historico' => collect()];
        }
        $guilda->load(['aventureiros' => fn ($query) => $query->select(['id', 'nickname', 'classe', 'nivel', 'guilda_id'])->withCount('quests')->orderByDesc('nivel')->orderBy('nickname')]);
        $quests = Quest::query()->whereHas('aventureiro', fn ($query) => $query->where('guilda_id', $guilda->id));
        $sucessos = (clone $quests)->where('sucesso', true)->with('dungeon')->get();

        return [
            'guildas' => $guildas,
            'guilda' => $guilda,
            'resumo' => [
                'membros' => $guilda->aventureiros->count(),
                'quests' => (clone $quests)->count(),
                'sucessos' => $sucessos->count(),
                'xp' => $sucessos->sum(fn (Quest $quest) => $quest->dungeon?->xp_recompensa ?? 0),
            ],
            'historico' => (clone $quests)->with(['aventureiro', 'dungeon'])->orderByDesc('data_quest')->orderByDesc('id')->limit(5)->get(),
        ];
    }
}
