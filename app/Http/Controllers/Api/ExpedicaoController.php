<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExplorarDungeonRequest;
use App\Http\Resources\QuestResource;
use App\Models\Dungeon;
use App\Services\ExpedicaoService;

class ExpedicaoController extends Controller
{
    public function store(ExplorarDungeonRequest $request, ExpedicaoService $expedicoes): QuestResource
    {
        $dados = $request->validated();

        return new QuestResource($expedicoes->explorar($request->user(), Dungeon::findOrFail($dados['dungeon_id']), $dados['submission_token']));
    }
}
