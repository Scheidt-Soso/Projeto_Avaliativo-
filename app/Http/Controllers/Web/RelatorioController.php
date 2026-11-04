<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\RelatorioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RelatorioController extends Controller
{
    public function __construct(private RelatorioService $relatorios) {}

    public function index(Request $request): View
    {
        $catalogo = $this->relatorios->catalogo();
        $dados = $request->validate(['aba' => ['nullable', Rule::in(array_keys($catalogo))], 'busca' => ['nullable', 'string', 'max:100']]);
        $aba = $dados['aba'] ?? 'ranking';
        return view('relatorios.index', ['catalogo' => $catalogo, 'aba' => $aba, 'meta' => $catalogo[$aba], 'busca' => $dados['busca'] ?? '', 'registros' => $this->relatorios->consultar($aba, $dados['busca'] ?? '')]);
    }

    public function atualizar(): RedirectResponse
    {
        $this->relatorios->atualizar();
        return to_route('relatorios.index')->with('sucesso', 'Ranking atualizado com as quests atuais.');
    }
}
