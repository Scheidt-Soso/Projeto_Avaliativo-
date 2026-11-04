<x-app-shell :title="$guilda?->nome ?? 'Guildas'" active="quartel" :guilda="$guilda">
    <x-slot:actions>
        <button class="guild-switch" type="button" data-open-guilds><x-pixel-icon name="shield" /><span>{{ $guilda?->nome ?? 'Escolher guilda' }}</span><x-pixel-icon name="chevron" /></button>
    </x-slot:actions>
            <section class="dashboard-heading" id="quartel"><div><p class="eyebrow"><span class="square-dot"></span> A BANDEIRA AINDA ESTÁ DE PÉ</p><h1>{{ $guilda?->nome ?? 'Nenhuma guilda encontrada' }}<span class="cursor-mark" aria-hidden="true">_</span></h1><p class="heading-description">{{ $resumo['membros'] }} aventureiros. Uma bandeira. Histórias que ainda não terminaram.</p></div><div class="guild-identification"><span class="identification-number">#{{ str_pad($guilda?->id ?? 0, 3, '0', STR_PAD_LEFT) }}</span><small>REGISTRO DA GUILDA</small>@if ($guilda?->data_fundacao)<span class="founding-date">DESDE {{ \Carbon\Carbon::parse($guilda->data_fundacao)->format('d.m.Y') }}</span>@endif</div></section>
            <section class="stats-strip" aria-label="Estatísticas da guilda">
                <div><span class="stat-label">MEMBROS</span><strong data-count="{{ $resumo['membros'] }}">{{ str_pad($resumo['membros'], 2, '0', STR_PAD_LEFT) }}</strong><small>sob a mesma bandeira</small></div>
                <div><span class="stat-label">EXPEDIÇÕES</span><strong data-count="{{ $resumo['quests'] }}">{{ str_pad($resumo['quests'], 2, '0', STR_PAD_LEFT) }}</strong><small>registradas no arquivo</small></div>
                <div><span class="stat-label">TAXA DE SUCESSO</span><strong>{{ $resumo['quests'] ? round($resumo['sucessos'] / $resumo['quests'] * 100) : 0 }}<span>%</span></strong><small>{{ $resumo['sucessos'] }} retornos vitoriosos</small></div>
                <div><span class="stat-label">XP CONQUISTADO</span><strong>{{ number_format($resumo['xp'], 0, ',', '.') }}</strong><small>em expedições bem-sucedidas</small></div>
            </section>
            <div class="dashboard-grid">
                <div class="main-column">
                    @include('guildas.partials.headquarters')
                    @include('guildas.partials.members')
                </div>
                <div class="side-column">
                    @include('guildas.partials.expedition')
                    <section class="chronicle-section" id="cronicas" aria-labelledby="chronicle-title"><div class="section-heading"><h2 id="chronicle-title"><span class="section-number">04</span> ECOS DO QUARTEL</h2><span class="section-caption">ÚLTIMOS REGISTROS</span></div><ol class="chronicle-list">@forelse ($historico as $quest)<li><span class="chronicle-marker {{ $quest->sucesso ? 'is-success' : '' }}" aria-label="{{ $quest->sucesso ? 'Sucesso' : 'Derrota' }}">{{ $quest->sucesso ? '+' : '×' }}</span><div><p><strong>{{ $quest->aventureiro?->nickname ?? 'Aventureiro removido' }}</strong> {{ $quest->sucesso ? 'retornou de' : 'foi derrotado em' }} <strong>{{ $quest->dungeon?->nome ?? 'Dungeon removida' }}</strong>.</p><span>{{ \Carbon\Carbon::parse($quest->data_quest)->format('d.m.Y') }} <i>/</i> {{ $quest->sucesso ? '+'.($quest->dungeon?->xp_recompensa ?? 0).' XP' : 'SEM RECOMPENSA' }}</span></div></li>@empty<li class="empty-copy">* O arquivo está em silêncio. A primeira história ainda será escrita.</li>@endforelse</ol></section>
                </div>
            </div>
    <dialog class="pixel-dialog guild-picker" aria-labelledby="guild-picker-title"><button class="dialog-close" type="button" data-close-dialog aria-label="Fechar"><x-pixel-icon name="close" /></button><p class="eyebrow">OUTRAS BANDEIRAS</p><h2 id="guild-picker-title">Escolha seu quartel.</h2><p class="dialog-description">Cada guilda tem seus próprios nomes e histórias.</p><div class="guild-list">@foreach ($guildas as $item)<a href="{{ route('guildas.dashboard', ['guilda' => $item->id]) }}" class="guild-option {{ $item->id === $guilda?->id ? 'is-current' : '' }}"><x-pixel-icon name="shield" /><span><strong>{{ $item->nome }}</strong><small>{{ $item->aventureiros_count }} MEMBROS · NÍVEL MÍNIMO {{ $item->nivel_minimo }}</small></span><span>{{ $item->id === $guilda?->id ? '◀' : '↗' }}</span></a>@endforeach</div></dialog>
    <dialog class="pixel-dialog member-dialog" aria-labelledby="member-dialog-title"><button class="dialog-close" type="button" data-close-dialog aria-label="Fechar"><x-pixel-icon name="close" /></button><p class="eyebrow">FICHA DO AVENTUREIRO</p><h2 id="member-dialog-title" data-detail-name></h2><dl class="member-details"><div><dt>CLASSE</dt><dd data-detail-class></dd></div><div><dt>NÍVEL</dt><dd data-detail-level></dd></div><div><dt>EXPEDIÇÕES</dt><dd data-detail-quests></dd></div><div><dt>GUILDA</dt><dd>{{ $guilda?->nome }}</dd></div></dl><button class="primary-action" type="button" data-choose-member>PREPARAR EXPEDIÇÃO <x-pixel-icon name="arrow" /></button></dialog>
    @include('guildas.partials.dungeon-atlas')
    <div class="expedition-loading" hidden role="status" aria-live="polite"><div class="loading-sigil"><x-pixel-icon name="flag" /></div><p>SALVANDO DESTINO<span>...</span></p><small>ANOTANDO O CAMINHO ESCOLHIDO</small></div>

</x-app-shell>
