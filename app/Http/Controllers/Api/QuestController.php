<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\QuestResource;
use App\Models\Aventureiro;
use App\Models\Dungeon;
use App\Models\Quest;
use App\Services\QuestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class QuestController extends Controller
{
    public function __construct(private QuestService $service)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return QuestResource::collection($this->service->listar());
    }

    /** O jogo chama esta rota ao fim de cada run. */
    public function store(Request $request): QuestResource
    {
        $dados = $request->validate([
            'aventureiro_id' => ['required', 'integer', Rule::exists(Aventureiro::class, 'id')],
            'dungeons_id' => ['required', 'integer', Rule::exists(Dungeon::class, 'id')],
            'data_exploracao' => ['nullable', 'date'],
            'sucesso' => ['required', 'boolean'],
        ]);

        return new QuestResource($this->service->criar($dados));
    }

    public function show(Quest $quest): QuestResource
    {
        return new QuestResource($this->service->exibir($quest));
    }

    public function update(Request $request, Quest $quest): QuestResource
    {
        $dados = $request->validate([
            'aventureiro_id' => ['sometimes', 'required', 'integer', Rule::exists(Aventureiro::class, 'id')],
            'dungeons_id' => ['sometimes', 'required', 'integer', Rule::exists(Dungeon::class, 'id')],
            'data_exploracao' => ['sometimes', 'required', 'date'],
            'sucesso' => ['sometimes', 'required', 'boolean'],
        ]);

        return new QuestResource($this->service->atualizar($quest, $dados));
    }

    public function destroy(Quest $quest): JsonResponse
    {
        $this->service->excluir($quest);

        return response()->json(null, 204);
    }

    public function registrarQuest(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'data_quest' => ['required', 'date', 'before_or_equal:today'],
            'aventureiro_id' => ['required', 'integer', Rule::exists(Aventureiro::class, 'id')],
            'dungeon_id' => ['required', 'integer', Rule::exists(Dungeon::class, 'id')],
            'resultado' => ['required', 'string', Rule::in(['sucesso', 'falha'])],
        ]);

        $this->service->registrarQuest(
            $dados['data_quest'],
            $dados['aventureiro_id'],
            $dados['dungeon_id'],
            $dados['resultado']
        );

        return response()->json(['message' => 'Quest registrada com sucesso.'], 200);
    }
}
