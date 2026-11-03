<?php 

namespace App\Services;

use App\Models\Aventureiro;
use Illuminate\Support\Facades\Hash;

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

    public function login(array $dados)
    {
        $aventureiro = Aventureiro::where('nickname', $dados['nickname'])->first();

        if (!$aventureiro || !Hash::check($dados['senha'], $aventureiro->senha)) {
            return null;
        }

        return $aventureiro;
    }
}