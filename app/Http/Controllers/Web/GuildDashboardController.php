<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Guilda;
use App\Models\Aventureiro;
use App\Services\GuildDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GuildDashboardController extends Controller
{
    public function __construct(private GuildDashboardService $dashboard) {}

    public function index(Request $request): View
    {
        $data = $request->validate(['guilda' => ['nullable', 'integer', Rule::exists(Guilda::class, 'id')]]);
        $dashboard = $this->dashboard->dashboard(isset($data['guilda']) ? (int) $data['guilda'] : null);
        $oferta = $request->session()->get('expedicao.oferta');

        return view('guildas.dashboard', $dashboard + [
            'oferta' => $oferta && $oferta['guilda_id'] === $dashboard['guilda']?->id ? $oferta : null,
        ]);
    }

    public function sortear(Request $request, Guilda $guilda): RedirectResponse
    {
        $data = $request->validate([
            'aventureiro_id' => ['required', 'integer', Rule::exists(Aventureiro::class, 'id')->where('guilda_id', $guilda->id)],
        ], ['aventureiro_id.exists' => 'Escolha um aventureiro que pertença a esta guilda.']);
        $dungeon = $this->dashboard->sortearDungeon();
        $request->session()->put('expedicao.oferta', [
            'guilda_id' => $guilda->id,
            'aventureiro_id' => (int) $data['aventureiro_id'],
            'dungeon' => $dungeon->only(['id', 'nome', 'dificuldade', 'xp_recompensa']),
            'token' => (string) Str::uuid(),
        ]);

        return to_route('guildas.dashboard', ['guilda' => $guilda->id])
            ->with('sucesso', 'Dungeon sorteada. A escolha do destino não altera o nível do aventureiro.')
            ->with('expedicao_sorteada', true);
    }
}
