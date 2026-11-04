<?php

namespace App\Support;

use App\Models\Guilda;

class GuildBaseAppearance
{
    public function forGuild(?Guilda $guilda): array
    {
        $defaults = config('guild-bases.default');
        $profile = config('guild-bases.guilds', [])[$guilda?->nome] ?? [];

        return array_replace($defaults, $profile, [
            'seed' => (int) sprintf('%u', crc32(($guilda?->id ?? 0).':'.($guilda?->nome ?? ''))),
        ]);
    }
}
