<section class="expedition-panel panel" id="expedicoes" aria-labelledby="expedition-title">
    <div class="panel-heading"><h2><span class="section-number">03</span> PREPARAR EXPEDIÇÃO</h2><x-pixel-icon name="door" /></div>
    <div class="expedition-content">
        <div class="portal-emblem" aria-hidden="true"><span class="portal-orbit"></span><x-pixel-icon name="door" /><span class="portal-spark portal-spark--one">+</span><span class="portal-spark portal-spark--two">+</span><span class="portal-spark portal-spark--three">·</span></div>
        <h3 id="expedition-title">Trace o seu caminho.</h3>
        <p class="expedition-intro">Escolha o aventureiro.<br>Marque o destino no mapa.</p>
        @php($disponiveis = ($guilda?->aventureiros ?? collect())->filter(fn ($member) => auth()->user()->can('gerenciar-cadastros') || $member->id === auth()->id()))
        @if ($guilda && $disponiveis->isNotEmpty() && $dungeons->isNotEmpty())
            <form method="post" action="{{ route('guildas.expedicao.selecionar', $guilda) }}" data-expedition-form>
                @csrf
                <label class="field-label" for="expedition-member">AVENTUREIRO</label>
                <div class="pixel-select">
                    <select id="expedition-member" name="aventureiro_id" required>
                        @foreach ($disponiveis as $member)
                            <option value="{{ $member->id }}" @selected(old('aventureiro_id', $destino['aventureiro']->id ?? null) == $member->id)>{{ $member->nickname }} · NV. {{ $member->nivel }}</option>
                        @endforeach
                    </select><x-pixel-icon name="chevron" />
                </div>
                <label class="field-label" for="expedition-dungeon">DUNGEON</label>
                <div class="pixel-select">
                    <select id="expedition-dungeon" name="dungeon_id" required>
                        <option value="">Escolha um destino</option>
                        @foreach ($dungeons as $dungeon)
                            <option value="{{ $dungeon->id }}" @selected(old('dungeon_id', $destino['dungeon']->id ?? null) == $dungeon->id)>{{ $dungeon->nome }} · {{ $dungeon->dificuldade ?: 'Sem classificação' }} · {{ number_format($dungeon->xp_recompensa, 0, ',', '.') }} XP</option>
                        @endforeach
                    </select><x-pixel-icon name="chevron" />
                </div>
                <button type="submit" class="primary-action"><x-pixel-icon name="flag" /><span>CONFIRMAR DESTINO</span><span>↗</span></button>
            </form>
        @elseif ($dungeons->isEmpty())
            <p class="empty-copy">* Nenhuma dungeon foi cadastrada.</p>
        @else
            <p class="empty-copy">{{ auth()->user()->can('gerenciar-cadastros') ? 'Esta guilda ainda não possui aventureiros para uma expedição.' : 'Seu aventureiro ainda não faz parte desta guilda.' }}</p>
            <a class="text-link" href="{{ route('perfil.show') }}">Abrir meu aventureiro ↗</a>
        @endif
        @if ($resultadoExpedicao)
            <x-expedition-result :quest="$resultadoExpedicao" />
        @endif
        @if ($destino)
            <div class="dungeon-selection" tabindex="-1" @if(session('destino_selecionado')) data-new-selection @endif>
                <p class="field-label">DESTINO DE {{ $destino['aventureiro']->nickname }}</p>
                <h4>{{ $destino['dungeon']->nome }}</h4>
                <div class="selection-metadata"><span>{{ $destino['dungeon']->dificuldade ?: 'Sem classificação' }}</span><strong>{{ number_format($destino['dungeon']->xp_recompensa, 0, ',', '.') }} XP</strong></div>
                <div class="destination-result" data-destination-result>
                    <p class="field-label">ÚLTIMO RESULTADO DE {{ $destino['aventureiro']->nickname }} NESTA DUNGEON</p>
                    @if($destino['ultimaQuest'])
                        <span class="result-badge {{ $destino['ultimaQuest']->sucesso ? 'is-success' : '' }}">{{ $destino['ultimaQuest']->sucesso ? '✓ Sucesso' : '× Falha' }}</span>
                        <time datetime="{{ $destino['ultimaQuest']->data_quest }}">{{ \App\Support\RecordDisplay::value($destino['ultimaQuest'], 'data_quest') }}</time>
                        <a class="text-link" href="{{ route('cadastros.quests.show', $destino['ultimaQuest']->id) }}">Ver registro ↗</a>
                    @else
                        <p class="selection-hint">Sem expedições registradas para este aventureiro nesta dungeon.</p>
                    @endif
                </div>
                @if($destino['aventureiro']->id === auth()->id())
                    <p class="selection-hint">Entre na dungeon. O resultado é sorteado ao explorar: 50% de chance de sucesso. Uma vitória concede {{ number_format($destino['dungeon']->xp_recompensa, 0, ',', '.') }} XP.</p>
                    <form method="post" action="{{ route('expedicoes.store') }}" data-explore-form>
                        @csrf
                        <input type="hidden" name="dungeon_id" value="{{ $destino['dungeon']->id }}">
                        <input type="hidden" name="submission_token" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                        <button class="primary-action" type="submit"><x-pixel-icon name="door" /><span>EXPLORAR DUNGEON</span><x-pixel-icon name="arrow" /></button>
                    </form>
                @elseif(auth()->user()->can('gerenciar-cadastros'))
                    <a class="secondary-action" href="{{ route('cadastros.quests.create', ['aventureiro_id' => $destino['aventureiro']->id, 'dungeon_id' => $destino['dungeon']->id]) }}">REGISTRAR RESULTADO <x-pixel-icon name="arrow" /></a>
                @endif
                <a class="text-link expedition-history-link" href="{{ route('perfil.show', ['dungeon' => $destino['dungeon']->id]) }}#suas-expedicoes">ACOMPANHAR EXPEDIÇÕES ↗</a>
            </div>
        @endif
        <button class="atlas-open" type="button" data-open-atlas><x-pixel-icon name="book" /> CONSULTAR ATLAS <span>{{ $dungeons->count() }} DESTINOS</span></button>
    </div>
    <div class="expedition-footnote"><span>?</span><p>Conheça os caminhos.<br>Escolha a próxima história.</p></div>
</section>
