<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guilda extends Model
{
    protected $table = 'rpg_trabalho_bd.guildas';

    protected $fillable = [
        'nome',
        'nivel_minimo',
        'data_fundacao',
    ];

    // isso serve para registrar que a tabela não possui os campos created_at e updated_at
    public $timestamps = false;

    public function aventureiros()
    {
        return $this->hasMany(Aventureiro::class, 'guilda_id');
    }
}
