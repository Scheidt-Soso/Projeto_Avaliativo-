<?php

namespace App\Services;

use App\Models\Aventureiro;
use App\Models\Dungeon;
use App\Models\Guilda;

class PerfilService
{
    public function __construct(private ProcedureService $procedures) {}

    public function dados(Aventureiro $aventureiro): array
    {
        return [
            'aventureiro' => $aventureiro->refresh()->load('guilda'),
            'progresso' => $this->procedures->progresso($aventureiro->id),
            'guildas' => Guilda::query()->select(['id', 'nome', 'nivel_minimo', 'data_fundacao'])->withCount('aventureiros')->orderBy('nome')->get(),
            'dungeons' => Dungeon::query()->orderBy('xp_recompensa')->orderBy('nome')->get(),
            'quests' => $aventureiro->quests()->with(['aventureiro', 'dungeon'])->orderByDesc('data_quest')->orderByDesc('id')->limit(10)->get(),
            'totalQuests' => $aventureiro->quests()->count(),
        ];
    }
}
