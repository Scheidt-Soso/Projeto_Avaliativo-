<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestResource;
use App\Models\Aventureiro;
use App\Models\Dungeon;
use App\Models\Quest;
use App\Services\ProcedureService;
use App\Services\QuestService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class QuestController extends Controller
{
    public function __construct(
        private QuestService $service,
        private ProcedureService $procedures,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return QuestResource::collection($this->service->listar());
    }

    /** Registra o resultado de uma expedição pela API. */
    public function store(Request $request): QuestResource
    {
        $regras = [
            'dungeon_id' => ['required', 'integer', Rule::exists(Dungeon::class, 'id')],
            'data_quest' => ['nullable', 'date'],
            'sucesso' => ['required', 'boolean'],
        ];

        if ($request->user()?->role === 'admin') {
            $regras['aventureiro_id'] = ['required', 'integer', Rule::exists(Aventureiro::class, 'id')];
        }

        $dados = $request->validate($regras);

        if ($request->user()?->role !== 'admin') {
            $dados['aventureiro_id'] = $request->user()->id;
        }

        return new QuestResource($this->service->criar($dados));
    }

    public function registrar(Request $request): JsonResponse
    {
        $regras = [
            'dungeon_id' => ['required', 'integer', Rule::exists(Dungeon::class, 'id')],
            'data_quest' => ['required', 'date', 'before_or_equal:today'],
            'sucesso' => ['required', 'boolean'],
        ];

        if ($request->user()?->role === 'admin') {
            $regras['aventureiro_id'] = ['required', 'integer', Rule::exists(Aventureiro::class, 'id')];
        }

        $dados = $request->validate($regras);

        $aventureiroId = $request->user()?->role === 'admin'
            ? $dados['aventureiro_id']
            : $request->user()->id;

        try {
            $progresso = $this->procedures->registrarQuest(
                $aventureiroId,
                $dados['dungeon_id'],
                $dados['data_quest'],
                $dados['sucesso'],
            );
        } catch (QueryException $e) {
            return response()->json(['message' => $this->mensagemDoBanco($e)], 422);
        }

        return response()->json([
            'message' => 'Quest registrada com sucesso.',
            'xp_total' => $progresso->xp_total,
            'nivel_atual' => $progresso->nivel_atual,
        ], 201);
    }

    public function show(Quest $quest): QuestResource
    {
        return new QuestResource($this->service->exibir($quest));
    }

    public function update(Request $request, Quest $quest): QuestResource
    {
        $dados = $request->validate([
            'aventureiro_id' => ['sometimes', 'required', 'integer', Rule::exists(Aventureiro::class, 'id')],
            'dungeon_id' => ['sometimes', 'required', 'integer', Rule::exists(Dungeon::class, 'id')],
            'data_quest' => ['sometimes', 'required', 'date'],
            'sucesso' => ['sometimes', 'required', 'boolean'],
        ]);

        return new QuestResource($this->service->atualizar($quest, $dados));
    }

    public function destroy(Quest $quest): JsonResponse
    {
        $this->service->excluir($quest);

        return response()->json(null, 204);
    }
}
