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
        $dados = $request->validate([
            'nickname' => ['required', 'string', 'max:50', Rule::unique(Aventureiro::class, 'nickname')],
            'classe' => ['required', 'string', 'max:50'],
            'nivel' => ['nullable', 'integer', 'min:1'],
            'guilda_id' => ['nullable', 'integer', Rule::exists(Guilda::class, 'id')],
        ]);

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
        $dados = $request->validate([
            'nickname' => ['sometimes', 'required', 'string', 'max:50', Rule::unique(Aventureiro::class, 'nickname')->ignore($aventureiro->id)],
            'classe' => ['sometimes', 'required', 'string', 'max:50'],
            'nivel' => ['nullable', 'integer', 'min:1'],
            'guilda_id' => ['nullable', 'integer', Rule::exists(Guilda::class, 'id')],
        ]);

        return new AventureiroResource($this->service->atualizar($aventureiro, $dados));
    }

    public function destroy(Aventureiro $aventureiro): JsonResponse
    {
        $this->service->excluir($aventureiro);

        return response()->json(null, 204);
    }
}
