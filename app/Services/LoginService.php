<?php

namespace App\Services;

use App\Models\Aventureiro;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginService
{
    public function authenticate(#[\SensitiveParameter] array $dados): bool
    {
        return Auth::attempt(['nickname' => $dados['nickname'], 'password' => $dados['senha']]);
    }

    public function classesDisponiveis(): Collection
    {
        return Aventureiro::query()->distinct()->orderBy('classe')->pluck('classe');
    }

    public function register(#[\SensitiveParameter] array $dados): Aventureiro
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
