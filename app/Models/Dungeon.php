<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dungeon extends Model
{
    protected $table = 'rpg_trabalho_bd.dungeons';

    protected $fillable = [
        'nome',
        'dificuldade',
        'xp_recompensa',
    ];

    // isso serve para registrar que a tabela não possui os campos created_at e updated_at
    public $timestamps = false;

    public function quests()
    {
        return $this->hasMany(Quest::class, 'dungeons_id');
    }
}
