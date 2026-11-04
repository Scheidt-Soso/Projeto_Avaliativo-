<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Aventureiro;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nickname' => ['required', 'string', 'max:50', Rule::unique(Aventureiro::class, 'nickname')],
            'classe' => ['required', 'string', 'max:50'],
            'senha' => \App\Support\RecordRules::password(),
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($dados) {
            $aventureiro = app(\App\Services\LoginService::class)->register($dados);

            return response()->json(['aventureiro' => $aventureiro, 'token' => $aventureiro->createToken('api')->plainTextToken], 201);
        });
    }

    public function login(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nickname' => ['required', 'string', 'max:50'],
            'senha' => ['required', 'string', 'max:255'],
        ]);

        $aventureiro = Aventureiro::where('nickname', $dados['nickname'])->first();

        if (! $aventureiro || ! Hash::check($dados['senha'], $aventureiro->senha)) {
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
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $token->delete();
        } else {
            \Illuminate\Support\Facades\Auth::guard('web')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        return response()->json(['message' => 'Token revogado com sucesso.']);
    }
}
