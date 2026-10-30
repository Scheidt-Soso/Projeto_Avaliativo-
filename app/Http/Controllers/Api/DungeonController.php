<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DungeonResource;
use App\Models\Dungeon;
use App\Services\DungeonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DungeonController extends Controller
{
    public function __construct(private DungeonService $service)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return DungeonResource::collection($this->service->listar());
    }

    public function store(Request $request): DungeonResource
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'dificuldade' => ['nullable', 'string', 'max:20'],
            'xp_recompensa' => ['required', 'integer', 'min:0'],
        ]);

        return new DungeonResource($this->service->criar($dados));
    }

    public function show(Dungeon $dungeon): DungeonResource
    {
        return new DungeonResource($this->service->exibir($dungeon));
    }

    public function update(Request $request, Dungeon $dungeon): DungeonResource
    {
        $dados = $request->validate([
            'nome' => ['sometimes', 'required', 'string', 'max:100'],
            'dificuldade' => ['nullable', 'string', 'max:20'],
            'xp_recompensa' => ['sometimes', 'required', 'integer', 'min:0'],
        ]);

        return new DungeonResource($this->service->atualizar($dungeon, $dados));
    }

    public function destroy(Dungeon $dungeon): JsonResponse
    {
        $this->service->excluir($dungeon);

        return response()->json(null, 204);
    }
}
