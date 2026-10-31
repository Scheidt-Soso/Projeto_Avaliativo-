<?php 

namespace App\Services;

use App\Models\Aventureiro;

class LoginService
{
    public function register(array $dados)
    {
        return Aventureiro::create([
            'nickname' => $dados['nickname'],
            'classe' => $dados['classe'],
            'senha' => Hash::make($dados['senha']),
            'role' => 'jogador',
            'nivel' => 1,
        ]);
    }

    
}