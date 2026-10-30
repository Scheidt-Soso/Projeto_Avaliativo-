<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DungeonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'dificuldade' => $this->dificuldade,
            'xp_recompensa' => $this->xp_recompensa,
        ];
    }
}
