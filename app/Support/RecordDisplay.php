<?php

namespace App\Support;

use Carbon\Carbon;

class RecordDisplay
{
    public static function value(object $registro, string $campo): string
    {
        $value = data_get($registro, $campo);
        if ($campo === 'sucesso') return $value ? 'Sucesso' : 'Falha';
        if ($value === null || $value === '') return $campo === 'guilda.nome' ? 'Sem guilda' : 'Não informado';
        if (in_array($campo, ['data_fundacao', 'data_quest'])) return Carbon::parse($value)->format('d/m/Y');
        if ($campo === 'xp_recompensa') return number_format($value, 0, ',', '.').' XP';
        if ($campo === 'role') return $value === 'admin' ? 'Administrador' : 'Jogador';

        return (string) $value;
    }
}
