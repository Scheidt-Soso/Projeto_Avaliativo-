<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestResource;
use App\Models\Quest;
use App\Services\ProcedureService;
use App\Services\QuestService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

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
        Gate::authorize('gerenciar-cadastros');
        $request->mergeIfMissing(['data_quest' => now()->toDateString()]);
        $dados = $request->validate(\App\Support\RecordRules::for('quests'));

        return new QuestResource($this->service->criar($dados));
    }

    public function registrar(Request $request): JsonResponse
    {
        Gate::authorize('gerenciar-cadastros');
        $dados = $request->validate(\App\Support\RecordRules::for('quests'));

        try {
            $progresso = $this->procedures->registrarQuest(
                $dados['aventureiro_id'],
                $dados['dungeon_id'],
                $dados['data_quest'],
                $dados['sucesso'],
            );
        } catch (QueryException $e) {
            if (! \App\Support\DatabaseError::isValidation($e)) throw $e;
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
        Gate::authorize('gerenciar-cadastros');
        $dados = $request->validate(\App\Support\RecordRules::for('quests', $quest->id, true));

        return new QuestResource($this->service->atualizar($quest, $dados));
    }

    public function destroy(Quest $quest): JsonResponse
    {
        Gate::authorize('gerenciar-cadastros');
        $this->service->excluir($quest);

        return response()->json(null, 204);
    }
}
