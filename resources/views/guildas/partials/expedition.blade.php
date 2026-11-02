<section class="expedition-panel panel" id="expedicoes" aria-labelledby="expedition-title">
    <div class="panel-heading"><h2><span class="section-number">03</span> PREPARAR EXPEDIÇÃO</h2><x-pixel-icon name="door" /></div>
    <div class="expedition-content">
        <div class="portal-emblem" aria-hidden="true"><span class="portal-orbit"></span><x-pixel-icon name="door" /><span class="portal-spark portal-spark--one">+</span><span class="portal-spark portal-spark--two">+</span><span class="portal-spark portal-spark--three">·</span></div>
        <h3 id="expedition-title">Trace o seu caminho.</h3>
        <p class="expedition-intro">Escolha o aventureiro.<br>Marque o destino no mapa.</p>
        @if ($guilda && $guilda->aventureiros->isNotEmpty() && $dungeons->isNotEmpty())
            <form method="post" action="{{ route('guildas.expedicao.selecionar', $guilda) }}" data-expedition-form>
                @csrf
                <label class="field-label" for="expedition-member">AVENTUREIRO</label>
                <div class="pixel-select">
                    <select id="expedition-member" name="aventureiro_id" required>
                        @foreach ($guilda->aventureiros as $member)
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
            <p class="empty-copy">* Uma expedição precisa de um aventureiro nesta guilda.</p>
        @endif
        @if ($destino)
            <div class="dungeon-selection" tabindex="-1" @if(session('destino_selecionado')) data-new-selection @endif>
                <p class="field-label">DESTINO DE {{ $destino['aventureiro']->nickname }}</p>
                <h4>{{ $destino['dungeon']->nome }}</h4>
                <div class="selection-metadata"><span>{{ $destino['dungeon']->dificuldade ?: 'Sem classificação' }}</span><strong>{{ number_format($destino['dungeon']->xp_recompensa, 0, ',', '.') }} XP</strong></div>
                <p class="selection-hint">Destino preparado. O resultado e o XP dependem do registro da quest.</p>
            </div>
        @endif
        <button class="atlas-open" type="button" data-open-atlas><x-pixel-icon name="book" /> CONSULTAR ATLAS <span>{{ $dungeons->count() }} DESTINOS</span></button>
    </div>
    <div class="expedition-footnote"><span>?</span><p>Conheça os caminhos.<br>Escolha a próxima história.</p></div>
</section>
