<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\LoginService;

class LoginController extends Controller
{
    protected $loginService;

    public function __construct(LoginService $loginService)
    {
        $this->loginService = $loginService;
    }

    public function showRegister()
    {
        return view('jogador.register');
    }

    public function register(Request $request)
    {
        $dados = $request->validate([
            'nickname' => 'required|string|max:50|unique:pgsql.aventureiros,nickname',
            'classe' => 'required|string|max:50',
            'senha' => 'required|string|min:6|confirmed',
        ]);

        $this->loginService->register($dados);

        return redirect()->route('login')
            ->with('success', 'Cadastro realizado com sucesso!');
    }

    public function showLogin()
    {
        return view('jogador.login');
    }
}