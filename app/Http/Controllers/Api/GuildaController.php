<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GuildaResource;
use App\Models\Guilda;
use App\Services\GuildaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GuildaController extends Controller
{
    public function __construct(private GuildaService $service)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        return GuildaResource::collection($this->service->listar());
    }

    public function store(Request $request): GuildaResource
    {
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:100'],
            'nivel_minimo' => ['nullable', 'integer', 'min:1'],
            'data_fundacao' => ['nullable', 'date'],
        ]);

        return new GuildaResource($this->service->criar($dados));
    }

    public function show(Guilda $guilda): GuildaResource
    {
        return new GuildaResource($this->service->exibir($guilda));
    }

    public function update(Request $request, Guilda $guilda): GuildaResource
    {
        $dados = $request->validate([
            'nome' => ['sometimes', 'required', 'string', 'max:100'],
            'nivel_minimo' => ['nullable', 'integer', 'min:1'],
            'data_fundacao' => ['nullable', 'date'],
        ]);

        return new GuildaResource($this->service->atualizar($guilda, $dados));
    }

    public function destroy(Guilda $guilda): JsonResponse
    {
        $this->service->excluir($guilda);

        return response()->json(null, 204);
    }
}
