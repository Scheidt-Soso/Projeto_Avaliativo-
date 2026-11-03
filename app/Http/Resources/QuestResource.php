<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'aventureiro_id' => $this->aventureiro_id,
            'dungeon_id' => $this->dungeon_id,
            'data_quest' => $this->data_quest,
            'sucesso' => $this->sucesso,
            'aventureiro' => new AventureiroResource($this->whenLoaded('aventureiro')),
            'dungeon' => new DungeonResource($this->whenLoaded('dungeon')),
        ];
    }
}
