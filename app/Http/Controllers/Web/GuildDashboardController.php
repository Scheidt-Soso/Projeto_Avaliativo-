<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Guilda;
use App\Models\Aventureiro;
use App\Models\Dungeon;
use App\Services\GuildDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GuildDashboardController extends Controller
{
    public function __construct(private GuildDashboardService $dashboard) {}

    public function index(Request $request): View|RedirectResponse
    {
        $data = $request->validate(['guilda' => ['nullable', 'integer', Rule::exists(Guilda::class, 'id')]]);
        if (empty($data['guilda']) && $request->user()->role === 'jogador' && $request->user()->guilda_id === null) {
            return to_route('perfil.show');
        }
        $dashboard = $this->dashboard->dashboard(isset($data['guilda']) ? (int) $data['guilda'] : $request->user()->guilda_id);
        $selecao = $request->session()->get('expedicao.selecao');
        $destino = null;
        if (($selecao['guilda_id'] ?? null) === $dashboard['guilda']?->id && $dashboard['guilda']) {
            $aventureiro = $dashboard['guilda']->aventureiros->firstWhere('id', $selecao['aventureiro_id']);
            $dungeon = $dashboard['dungeons']->firstWhere('id', $selecao['dungeon_id']);
            if ($aventureiro && $dungeon && ($request->user()->can('gerenciar-cadastros') || $aventureiro->id === $request->user()->id)) {
                $destino = compact('aventureiro', 'dungeon');
            } else {
                $request->session()->forget('expedicao.selecao');
            }
        }

        return view('guildas.dashboard', $dashboard + [
            'destino' => $destino,
        ]);
    }

    public function selecionarDestino(Request $request, Guilda $guilda): RedirectResponse
    {
        $data = $request->validate([
            'aventureiro_id' => ['required', 'integer', Rule::exists(Aventureiro::class, 'id')->where('guilda_id', $guilda->id)],
            'dungeon_id' => ['required', 'integer', Rule::exists(Dungeon::class, 'id')],
        ], [
            'aventureiro_id.exists' => 'Escolha um aventureiro que pertença a esta guilda.',
            'dungeon_id.required' => 'Escolha a dungeon da expedição.',
            'dungeon_id.exists' => 'Esta dungeon não está mais cadastrada. Escolha outro destino.',
        ]);
        abort_unless($request->user()->can('gerenciar-cadastros') || (int) $data['aventureiro_id'] === $request->user()->id, 403);
        $request->session()->put('expedicao.selecao', [
            'guilda_id' => $guilda->id,
            'aventureiro_id' => (int) $data['aventureiro_id'],
            'dungeon_id' => (int) $data['dungeon_id'],
        ]);

        return to_route('guildas.dashboard', ['guilda' => $guilda->id])
            ->with('sucesso', 'Destino escolhido. A preparação não registra uma quest nem altera o nível.')
            ->with('destino_selecionado', true);
    }
}
