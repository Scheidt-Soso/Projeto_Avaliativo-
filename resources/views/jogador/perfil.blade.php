<x-app-shell title="Meu aventureiro" active="aventureiros">
    <section class="catalog-heading"><div><p class="eyebrow">SEU NOME NO ARQUIVO</p><h1>{{ $aventureiro->nickname }}<span class="cursor-mark" aria-hidden="true">_</span></h1><p>{{ $aventureiro->classe }} · {{ $aventureiro->guilda?->nome ?? 'Ainda sem guilda' }}</p></div><span class="profile-sigil"><x-member-sigil :member="$aventureiro" /></span></section>
    <section class="stats-strip profile-stats" aria-label="Progresso do aventureiro">
        <div><span class="stat-label">NÍVEL ATUAL</span><strong>{{ $aventureiro->nivel }}</strong><small>registrado no arquivo</small></div>
        <div><span class="stat-label">XP ACUMULADO</span><strong>{{ number_format($progresso->xp_total, 0, ',', '.') }}</strong><small>de quests bem-sucedidas</small></div>
        <div><span class="stat-label">SUAS QUESTS</span><strong>{{ $totalQuests }}</strong><small>expedições registradas</small></div>
        <div><span class="stat-label">PRÓXIMO NÍVEL</span><strong>{{ $progresso->nivel_calculado >= 100 ? 'MÁX.' : number_format(max(0, $progresso->nivel_calculado * 1000 - $progresso->xp_total), 0, ',', '.') }}</strong><small>{{ $progresso->nivel_calculado >= 100 ? 'limite de progressão alcançado' : 'XP restante pela progressão atual' }}</small></div>
    </section>
    <div class="profile-grid">
        <section class="panel profile-panel" aria-labelledby="join-title"><div class="panel-heading"><h2 id="join-title"><x-pixel-icon name="flag" /> SUA BANDEIRA</h2></div><div class="profile-panel-content"><h3>{{ $aventureiro->guilda ? 'Encontre seu próximo quartel.' : 'Uma guilda espera por você.' }}</h3><p>Escolha uma guilda compatível com seu nível. O nível mínimo de entrada aparece ao lado de cada nome.</p>
            @if($guildas->isNotEmpty())<form method="post" action="{{ route('perfil.guilda') }}" data-record-form>@csrf<label for="profile-guild">Guilda</label><select id="profile-guild" name="guilda_id" required><option value="">Escolha uma guilda</option>@foreach($guildas as $item)<option value="{{ $item->id }}" @disabled($item->nivel_minimo > $aventureiro->nivel) @selected(old('guilda_id') == $item->id)>{{ $item->nome }} · nível {{ $item->nivel_minimo }}{{ $item->nivel_minimo > $aventureiro->nivel ? ' (nível insuficiente)' : '' }}</option>@endforeach</select><button class="primary-action" type="submit"><span>{{ $aventureiro->guilda ? 'ATUALIZAR VÍNCULO' : 'ENTRAR NA GUILDA' }}</span><x-pixel-icon name="arrow" /></button></form>@else<p class="empty-copy">Ainda não há guildas cadastradas.</p>@endif
        </div></section>
        <section class="panel profile-panel" id="expedicoes" aria-labelledby="profile-quest-title"><div class="panel-heading"><h2 id="profile-quest-title"><x-pixel-icon name="door" /> SUAS EXPEDIÇÕES</h2></div><div class="profile-panel-content">
            @php($destinoConsultado = $dungeons->firstWhere('id', $dungeonSelecionada))
            <h3>{{ $destinoConsultado?->nome ?? 'Cada retorno deixa uma história.' }}</h3>
            <p>Escolha uma dungeon e explore. O resultado é sorteado com 50% de chance de sucesso; uma vitória concede o XP da dungeon e atualiza seu nível.</p>
            @if($destinoConsultado)<p>Destino escolhido · {{ $destinoConsultado->dificuldade ?: 'Sem classificação' }} · {{ number_format($destinoConsultado->xp_recompensa, 0, ',', '.') }} XP em caso de sucesso.</p>@endif
            @if($resultadoExpedicao)<x-expedition-result :quest="$resultadoExpedicao" />@endif
            @if($dungeons->isNotEmpty())
                <form method="post" action="{{ route('expedicoes.store') }}" data-explore-form>
                    @csrf
                    <input type="hidden" name="submission_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                    <label for="profile-dungeon">Dungeon</label>
                    <select id="profile-dungeon" name="dungeon_id" required>
                        <option value="">Escolha seu próximo destino</option>
                        @foreach($dungeons as $item)<option value="{{ $item->id }}" @selected(old('dungeon_id', $dungeonSelecionada) == $item->id)>{{ $item->nome }} · {{ $item->dificuldade ?: 'Sem classificação' }} · {{ number_format($item->xp_recompensa, 0, ',', '.') }} XP</option>@endforeach
                    </select>
                    <button class="primary-action" type="submit"><span>EXPLORAR DUNGEON</span><x-pixel-icon name="door" /></button>
                </form>
            @else
                <p class="empty-copy">Nenhuma dungeon foi cadastrada.</p>
            @endif
            @can('gerenciar-cadastros')
                <a class="primary-action" href="{{ route('cadastros.quests.create', array_filter(['aventureiro_id' => $aventureiro->id, 'dungeon_id' => $destinoConsultado?->id])) }}"><span>REGISTRAR RESULTADO</span><x-pixel-icon name="check" /></a>
            @else
                <a class="secondary-action" href="#suas-expedicoes">VER MEU HISTÓRICO <x-pixel-icon name="arrow" /></a>
            @endcan
        </div></section>
    </div>
    <section class="record-history" id="suas-expedicoes"><div class="section-heading"><h2>SUAS ÚLTIMAS EXPEDIÇÕES</h2><a class="text-link" href="{{ route('cadastros.quests.index', ['aventureiro_id' => $aventureiro->id]) }}">Ver todas ↗</a></div>@include('cadastros.history')</section>
</x-app-shell>
