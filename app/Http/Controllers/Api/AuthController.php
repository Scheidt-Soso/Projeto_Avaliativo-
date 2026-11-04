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
            'senha' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $aventureiro = Aventureiro::create([
            'nickname' => $dados['nickname'],
            'classe' => $dados['classe'],
            'senha' => Hash::make($dados['senha']),
            'role' => 'jogador',
            'nivel' => 1,
        ]);

        return response()->json([
            'aventureiro' => $aventureiro,
            'token' => $aventureiro->createToken('api')->plainTextToken,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'nickname' => ['required', 'string'],
            'senha' => ['required', 'string'],
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
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Token revogado com sucesso.']);
    }
}
