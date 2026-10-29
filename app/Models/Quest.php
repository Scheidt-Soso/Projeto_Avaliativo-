<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quest extends Model
{
    protected $table = 'quests';

    protected $fillable = [
        'aventureiro_id',
        'dungeon_id',
        'data_quest',
        'sucesso',
    ];

    // isso serve para registrar que a tabela não possui os campos created_at e updated_at
    public $timestamps = false;

    // Relacionamento com a tabela Aventureiro
    public function aventureiro()
    {
        return $this->belongsTo(Aventureiro::class, 'aventureiro_id');
    }

    // Relacionamento com a tabela Dungeon
    public function dungeon()
    {
        return $this->belongsTo(Dungeon::class, 'dungeon_id');
    }
}
