<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use app\Models\Aventureiro;
use app\Services\LoginService;

class loginController extends Controller
{
    public function register(Request $request){
        $dados = $request->validate([
        'nickname' => 'required|string|max:50|unique:rpg_trabalho_bd.aventureiros,nickname',
        'classe' => 'required|string|max:50',
        'senha' => 'required|string|min:6|confirmed',
    ]);

    $this->loginService->register($dados);

    return redirect()->route('jogador.login')
        ->with('success', 'Cadastro realizado com sucesso!');
    }

    
}
