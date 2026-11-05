<?php

namespace App\Services;

use App\Models\Aventureiro;
use App\Models\Dungeon;
use App\Models\Quest;
use Illuminate\Validation\ValidationException;
use Random\Randomizer;

class ExpedicaoService
{
    public function __construct(private QuestService $quests, private SubmissionService $submissoes, private Randomizer $random) {}

    public function explorar(Aventureiro $aventureiro, Dungeon $dungeon, string $token): Quest
    {
        $criada = null;
        $id = $this->submissoes->criar('expedicoes', $token, function () use ($aventureiro, $dungeon, &$criada) {
            // O sorteio ocorre uma única vez, dentro do bloqueio contra reenvios.
            $criada = $this->quests->criar([
                'aventureiro_id' => $aventureiro->id,
                'dungeon_id' => $dungeon->id,
                'sucesso' => $this->random->getInt(0, 1) === 1,
            ]);

            return $criada;
        });

        $quest = $criada ?? Quest::with(['aventureiro', 'dungeon'])->findOrFail($id);
        if ($quest->aventureiro_id !== $aventureiro->id || $quest->dungeon_id !== $dungeon->id) {
            throw ValidationException::withMessages(['submission_token' => 'Este envio já foi usado para outra expedição. Atualize a página.']);
        }
        $quest->wasRecentlyCreated = $criada !== null;

        return $quest;
    }

    public function resultado(Aventureiro $aventureiro, ?int $id): ?Quest
    {
        return $id ? $aventureiro->quests()->with(['aventureiro', 'dungeon'])->find($id) : null;
    }
}
