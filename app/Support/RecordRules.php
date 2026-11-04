<?php

namespace App\Support;

use App\Models\Aventureiro;
use App\Models\Dungeon;
use App\Models\Guilda;
use Illuminate\Validation\Rule;

class RecordRules
{
    public static function for(string $resource, ?int $id = null, bool $partial = false): array
    {
        $required = $partial ? 'sometimes|required' : 'required';
        $rules = match ($resource) {
            'guildas' => [
                'nome' => [$required, 'string', 'max:100'],
                'nivel_minimo' => [$required, 'integer', 'min:1', 'max:2147483647'],
                'data_fundacao' => ['nullable', 'date_format:Y-m-d'],
            ],
            'aventureiros' => [
                'nickname' => [$required, 'string', 'max:50', Rule::unique(Aventureiro::class, 'nickname')->ignore($id)],
                'classe' => [$required, 'string', 'max:50'],
                'nivel' => [$required, 'integer', 'min:1', 'max:2147483647'],
                'guilda_id' => ['nullable', 'integer', Rule::exists(Guilda::class, 'id')],
                'role' => [$required, Rule::in(['jogador', 'admin'])],
            ],
            'dungeons' => [
                'nome' => [$required, 'string', 'max:100'],
                'dificuldade' => ['nullable', 'string', 'max:20'],
                'xp_recompensa' => [$required, 'integer', 'min:0', 'max:2147483647'],
            ],
            'quests' => [
                'aventureiro_id' => [$required, 'integer', Rule::exists(Aventureiro::class, 'id')],
                'dungeon_id' => [$required, 'integer', Rule::exists(Dungeon::class, 'id')],
                'data_quest' => [$required, 'date_format:Y-m-d', 'before_or_equal:today'],
                'sucesso' => [$required, 'boolean'],
            ],
        };

        // Laravel expects each rule to be separate when using array notation.
        return array_map(fn ($fieldRules) => array_merge(...array_map(fn ($rule) => is_string($rule) ? explode('|', $rule) : [$rule], $fieldRules)), $rules);
    }

    public static function password(): array
    {
        return ['bail', 'required', 'string', 'min:6', 'confirmed', function (string $attribute, mixed $value, \Closure $fail) {
            if (strlen($value) > 72) $fail('A senha é muito longa. Use uma senha mais curta.');
        }];
    }
}
