<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExplorarDungeonRequest;
use App\Models\Dungeon;
use App\Services\ExpedicaoService;
use Illuminate\Http\RedirectResponse;

class ExpedicaoController extends Controller
{
    public function store(ExplorarDungeonRequest $request, ExpedicaoService $expedicoes): RedirectResponse
    {
        $dados = $request->validated();
        $quest = $expedicoes->explorar($request->user(), Dungeon::findOrFail($dados['dungeon_id']), $dados['submission_token']);
        $aventureiro = $quest->aventureiro;
        $request->session()->flash('expedicao_resultado_id', $quest->id);

        if ($aventureiro->guilda_id !== null) {
            $request->session()->put('expedicao.selecao', [
                'guilda_id' => $aventureiro->guilda_id, 'aventureiro_id' => $aventureiro->id, 'dungeon_id' => $quest->dungeon_id,
            ]);

            return redirect(route('guildas.dashboard', ['guilda' => $aventureiro->guilda_id]).'#expedicoes');
        }

        return redirect(route('perfil.show', ['dungeon' => $quest->dungeon_id]).'#expedicoes');
    }
}
