<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aventureiro extends Model
{
    protected $table = 'rpg_trabalho_bd.aventureiros';

    protected $fillable = [
        'nickname',
        'classe',
        'nivel',
        'guild_id',
    ];

    // isso serve para registrar que a tabela não possui os campos created_at e updated_at
    public $timestamps = false;

    // Relacionamento com a tabela Guilda
    public function guilda()
    {
        return $this->belongsTo(Guilda::class, 'guilda_id');
    }

    public function quests()
    {
        return $this->hasMany(Quest::class, 'aventureiro_id');
    }
}
