<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\LoginService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private LoginService $loginService) {}

    public function showLogin(): View
    {
        return view('jogador.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $dados = $request->validated();

        $key = 'login:'.hash('sha256', mb_strtolower($dados['nickname']).'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $segundos = RateLimiter::availableIn($key);
            throw ValidationException::withMessages(['nickname' => "Muitas tentativas. Aguarde {$segundos} segundos para tentar novamente."]);
        }

        if (! $this->loginService->authenticate($dados)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['nickname' => 'Nickname ou senha incorretos.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->forget(['aventureiro_id', 'expedicao']);

        return redirect()->intended(route('guildas.dashboard', array_filter(['guilda' => $request->user()->guilda_id])));
    }

    public function showRegister(): View
    {
        return view('jogador.register', ['classes' => $this->loginService->classesDisponiveis()]);
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $this->loginService->register($request->validated());

        return to_route('login')->with('sucesso', 'Cadastro realizado. Entre com seu nickname e sua senha.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login')->with('sucesso', 'Você saiu do quartel. Até a próxima.');
    }
}
