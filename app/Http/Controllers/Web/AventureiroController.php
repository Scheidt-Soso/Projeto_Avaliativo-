<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Aventureiro;
use App\Services\AventureiroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AventureiroController extends Controller
{
    public function __construct(private AventureiroService $aventureiros) {}

    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'busca' => ['nullable', 'string', 'max:50'],
            'classe' => ['nullable', 'string', 'max:50'],
        ]);
        $selecionado = $request->session()->get('aventureiro_id');

        return view('aventureiros.escolher', [
            'aventureiros' => $this->aventureiros->listarParaSelecao($filtros['busca'] ?? '', $filtros['classe'] ?? ''),
            'classes' => $this->aventureiros->classesDisponiveis(),
            'selecionado' => $selecionado ? $this->aventureiros->encontrarSelecionado((int) $selecionado) : null,
            'filtros' => $filtros,
        ]);
    }

    public function selecionar(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'aventureiro_id' => ['required', 'integer', Rule::exists(Aventureiro::class, 'id')],
        ]);
        $aventureiro = $this->aventureiros->encontrarParaSelecao((int) $dados['aventureiro_id']);
        $request->session()->put('aventureiro_id', $aventureiro->id);

        return to_route('aventureiros.escolher')->with('sucesso', $aventureiro->nickname.' foi escolhido. Sua jornada começa aqui.');
    }
}
