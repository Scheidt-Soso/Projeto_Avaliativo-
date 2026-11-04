<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Aventureiro extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'rpg_trabalho_bd.aventureiros';

    protected $authPasswordName = 'senha';

    // O banco original não possui coluna remember_token.
    protected $rememberTokenName = '';

    protected $hidden = ['senha'];

    protected $fillable = [
        'nickname',
        'classe',
        'nivel',
        'guilda_id',
        'senha',
        'role',
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
