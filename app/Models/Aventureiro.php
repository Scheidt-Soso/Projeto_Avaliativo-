<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

// nota: as models estão com uma base boa, apontam para o schema correto e tabela, 
// possuem os campos fillable e timestamps, e possuem os relacionamentos corretos
// o aventureiro com id = 103 foi criado com sucesso pelo tinker usando a model

class Aventureiro extends Authenticatable
{
    protected $table = 'rpg_trabalho_bd.aventureiros';

    protected $fillable = [
        'nickname',
        'classe',
        'nivel',
        'guilda_id',
        'senha',
        'role',
    ];

    protected $hidden = [
        'senha',
    ];

    public function getAuthPassword()
    {
        return $this->senha;
    }

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
