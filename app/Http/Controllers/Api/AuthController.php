<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\LoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(private LoginService $loginService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $dados = $request->validated();

        return DB::transaction(function () use ($dados) {
            $aventureiro = $this->loginService->register($dados);

            return response()->json(['aventureiro' => $aventureiro, 'token' => $aventureiro->createToken('api')->plainTextToken], 201);
        });
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $dados = $request->validated();

        $aventureiro = $this->loginService->validarCredenciais($dados);

        if (! $aventureiro) {
            return response()->json(['message' => 'Credenciais inválidas.'], 401);
        }

        return response()->json([
            'aventureiro' => $aventureiro,
            'token' => $aventureiro->createToken('api')->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        } else {
            Auth::guard('web')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        return response()->json(['message' => 'Token revogado com sucesso.']);
    }
}
