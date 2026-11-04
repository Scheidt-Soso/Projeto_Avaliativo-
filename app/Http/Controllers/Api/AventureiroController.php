<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AventureiroResource;
use App\Models\Aventureiro;
use App\Models\Guilda;
use App\Services\AventureiroService;
use App\Services\ProcedureService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class AventureiroController extends Controller
{
    public function __construct(
        private AventureiroService $service,
        private ProcedureService $procedures,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return AventureiroResource::collection($this->service->listar());
    }

    public function store(Request $request): AventureiroResource
    {
        $request->mergeIfMissing(['nivel' => 1, 'role' => 'jogador']);
        $rules = \App\Support\RecordRules::for('aventureiros');
        if ($request->filled('senha')) $rules['senha'] = \App\Support\RecordRules::password();
        $dados = $request->validate($rules);

        return new AventureiroResource($this->service->criar($dados));
    }

    public function vincularGuilda(Request $request, Aventureiro $aventureiro): AventureiroResource|JsonResponse
    {
        if ($request->user()?->role !== 'admin' && (int) $request->user()?->id !== (int) $aventureiro->id) {
            abort(403, 'Você só pode vincular o seu próprio aventureiro.');
        }

        $dados = $request->validate([
            'guilda_id' => ['required', 'integer', Rule::exists(Guilda::class, 'id')],
        ]);

        try {
            $this->procedures->vincularAventureiroGuilda($aventureiro->id, $dados['guilda_id']);
        } catch (QueryException $e) {
            if (! \App\Support\DatabaseError::isValidation($e)) throw $e;
            return response()->json(['message' => $this->mensagemDoBanco($e)], 422);
        }

        return new AventureiroResource($aventureiro->refresh()->load('guilda'));
    }

    public function show(Aventureiro $aventureiro): AventureiroResource
    {
        return new AventureiroResource($this->service->exibir($aventureiro));
    }

    public function update(Request $request, Aventureiro $aventureiro): AventureiroResource
    {
        $dados = $request->validate(\App\Support\RecordRules::for('aventureiros', $aventureiro->id, true));
        if ($aventureiro->id === $request->user()->id && isset($dados['role']) && $dados['role'] !== 'admin') {
            throw \Illuminate\Validation\ValidationException::withMessages(['role' => 'Mantenha o acesso administrativo da sua própria conta.']);
        }

        return new AventureiroResource($this->service->atualizar($aventureiro, $dados));
    }

    public function destroy(Request $request, Aventureiro $aventureiro): JsonResponse
    {
        if ($aventureiro->id === $request->user()->id) {
            throw \Illuminate\Validation\ValidationException::withMessages(['aventureiro' => 'Sua conta não pode ser excluída durante esta sessão.']);
        }
        $this->service->excluir($aventureiro);

        return response()->json(null, 204);
    }
}
