<?php

namespace App\Services;

use App\Models\Aventureiro;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginService
{
    public function validarCredenciais(#[\SensitiveParameter] array $dados): ?Aventureiro
    {
        $aventureiro = Aventureiro::where('nickname', $dados['nickname'])->first();

        if (! $aventureiro || ! Hash::check($dados['senha'], $aventureiro->senha)) {
            return null;
        }

        return $aventureiro;
    }

    public function authenticate(#[\SensitiveParameter] array $dados): bool
    {
        $aventureiro = $this->validarCredenciais($dados);

        if (! $aventureiro) {
            return false;
        }

        Auth::login($aventureiro);

        return true;
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
