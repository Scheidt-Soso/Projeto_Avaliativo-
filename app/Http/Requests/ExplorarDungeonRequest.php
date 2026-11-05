<?php

namespace App\Http\Requests;

use App\Models\Dungeon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExplorarDungeonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'dungeon_id' => ['required', 'integer', Rule::exists(Dungeon::class, 'id')],
            'submission_token' => ['required', 'uuid'],
            'aventureiro_id' => ['prohibited'],
            'sucesso' => ['prohibited'],
            'data_quest' => ['prohibited'],
            'nivel' => ['prohibited'],
            'xp_total' => ['prohibited'],
            'xp_recompensa' => ['prohibited'],
            'role' => ['prohibited'],
        ];
    }

    public function messages(): array
    {
        return ['*.prohibited' => 'O resultado e o progresso da expedição são definidos pelo servidor.'];
    }
}
