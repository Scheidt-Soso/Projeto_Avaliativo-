<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="O quartel da sua guilda. Gerencie aventureiros, descubra dungeons e acompanhe cada expedição.">
    <title>{{ $guilda?->nome ?? 'Guildas' }} · Coroa de Cinzas</title>
    <link rel="preload" href="{{ Vite::asset('public/fonts/VT323-Regular.ttf') }}" as="font" type="font/ttf" crossorigin>
    @vite(['resources/css/guild.css', 'resources/js/guild.js'])
</head>
<body class="guild-app">
    <a class="skip-link" href="#quartel">Ir ao painel</a>
    <aside class="sidebar">
        <a class="wordmark" href="{{ route('guildas.dashboard') }}"><span class="wordmark-icon"><x-pixel-icon name="shield" /></span><span>COROA<br>DE CINZAS<small>GUILD MANAGER / 01</small></span></a>
        <div class="sidebar-label">SEU TERRITÓRIO</div>
        <nav class="main-nav" aria-label="Seções do painel">
            <a href="#quartel" class="nav-item is-active"><x-pixel-icon name="flag" /><span>Quartel</span><small>01</small></a>
            <a href="#membros" class="nav-item"><x-pixel-icon name="people" /><span>Membros</span><small>{{ $resumo['membros'] }}</small></a>
            <a href="#expedicoes" class="nav-item"><x-pixel-icon name="door" /><span>Expedições</span><small>↗</small></a>
            <a href="#cronicas" class="nav-item"><x-pixel-icon name="book" /><span>Crônicas</span><small>{{ $resumo['quests'] }}</small></a>
        </nav>
        <div class="sidebar-message"><span class="tiny-star">✦</span><p>Um nome pode cair.<br>Uma guilda permanece.</p></div>
        <div class="sidebar-bottom"><span class="connection-dot"></span> QUARTEL CONECTADO<span class="version-tag">v.02</span></div>
    </aside>
    <div class="workspace">
        <header class="topbar"><span class="breadcrumb">ARQUIVO <span>/</span> GUILDAS <span>/</span> <b>QUARTEL</b></span><div class="topbar-actions"><button type="button" class="motion-toggle" aria-pressed="false" data-motion-toggle><x-pixel-icon name="pause" /><span>Pausar animações</span></button><span class="topbar-divider"></span><button class="guild-switch" type="button" data-open-guilds><x-pixel-icon name="shield" /><span>{{ $guilda?->nome ?? 'Escolher guilda' }}</span><x-pixel-icon name="chevron" /></button></div></header>
        <main>
            @if (session('sucesso'))<div class="system-message" role="status"><x-pixel-icon name="check" /><p>{{ session('sucesso') }}</p></div>@endif
            @if ($errors->any())<div class="system-message error" role="alert"><x-pixel-icon name="close" /><p>{{ $errors->first() }}</p></div>@endif
            <section class="dashboard-heading" id="quartel"><div><p class="eyebrow"><span class="square-dot"></span> A BANDEIRA AINDA ESTÁ DE PÉ</p><h1>{{ $guilda?->nome ?? 'Nenhuma guilda encontrada' }}<span class="cursor-mark" aria-hidden="true">_</span></h1><p class="heading-description">{{ $resumo['membros'] }} aventureiros. Uma bandeira. Histórias que ainda não terminaram.</p></div><div class="guild-identification"><span class="identification-number">#{{ str_pad($guilda?->id ?? 0, 3, '0', STR_PAD_LEFT) }}</span><small>REGISTRO DA GUILDA</small>@if ($guilda?->data_fundacao)<span class="founding-date">DESDE {{ \Carbon\Carbon::parse($guilda->data_fundacao)->format('d.m.Y') }}</span>@endif</div></section>
            <section class="stats-strip" aria-label="Estatísticas da guilda">
                <div><span class="stat-label">MEMBROS</span><strong data-count="{{ $resumo['membros'] }}">{{ str_pad($resumo['membros'], 2, '0', STR_PAD_LEFT) }}</strong><small>sob a mesma bandeira</small></div>
                <div><span class="stat-label">EXPEDIÇÕES</span><strong data-count="{{ $resumo['quests'] }}">{{ str_pad($resumo['quests'], 2, '0', STR_PAD_LEFT) }}</strong><small>registradas no arquivo</small></div>
                <div><span class="stat-label">TAXA DE SUCESSO</span><strong>{{ $resumo['quests'] ? round($resumo['sucessos'] / $resumo['quests'] * 100) : 0 }}<span>%</span></strong><small>{{ $resumo['sucessos'] }} retornos vitoriosos</small></div>
                <div><span class="stat-label">XP CONQUISTADO</span><strong>{{ number_format($resumo['xp'], 0, ',', '.') }}</strong><small>em expedições bem-sucedidas</small></div>
            </section>
            <div class="dashboard-grid">
                <div class="main-column">
                    <section class="headquarters panel" aria-labelledby="hq-title"><div class="panel-heading"><h2 id="hq-title"><span class="section-number">01</span> O QUARTEL</h2><span class="live-label"><span></span> HÁ VIDA AQUI</span></div><div class="scene-wrap"><canvas id="guild-scene" width="560" height="260" role="img" aria-label="Quartel em pixel art: castelo, estandartes, aventureiros e um portal de dungeon"></canvas><div class="scene-coordinate" aria-hidden="true">X: 042 / Y: 018</div><span class="scene-corner scene-corner--tl"></span><span class="scene-corner scene-corner--br"></span></div><div class="dialogue-box"><x-pixel-icon name="star" /><p data-typewriter="A névoa está quieta. Seus aventureiros, nem tanto.">A névoa está quieta. Seus aventureiros, nem tanto.</p><span class="dialogue-indicator" aria-hidden="true">▼</span></div><div class="hq-footer"><span><x-pixel-icon name="flag" /> TERRITÓRIO DA GUILDA</span><span>ENTRADA A PARTIR DO <b>NV. {{ str_pad($guilda?->nivel_minimo ?? 1, 2, '0', STR_PAD_LEFT) }}</b></span></div></section>
                    <section class="members-section" id="membros" aria-labelledby="members-title"><div class="section-heading"><h2 id="members-title"><span class="section-number">02</span> QUEM CARREGA A BANDEIRA</h2><span class="section-caption">{{ $resumo['membros'] }} MEMBROS</span></div><div class="member-table-wrap"><table class="member-table"><thead><tr><th>AVENTUREIRO</th><th>CLASSE</th><th>NÍVEL</th><th><span class="sr-only">Ações</span></th></tr></thead><tbody>
                        @forelse ($guilda?->aventureiros ?? [] as $member)
                        <tr><td><div class="member-identity"><span class="member-avatar"><x-member-sigil :member="$member" /></span><div><strong>{{ $member->nickname }}</strong><small>REGISTRO #{{ str_pad($member->id, 3, '0', STR_PAD_LEFT) }}</small></div></div></td><td><span class="class-tag">{{ $member->classe }}</span></td><td><span class="member-level">{{ str_pad($member->nivel, 2, '0', STR_PAD_LEFT) }}</span></td><td><button class="member-action" type="button" data-member-name="{{ $member->nickname }}" data-member-id="{{ $member->id }}" data-member-class="{{ $member->classe }}" data-member-level="{{ $member->nivel }}" data-member-quests="{{ $member->quests_count ?? 0 }}" aria-label="Ver ficha de {{ $member->nickname }}">FICHA <x-pixel-icon name="arrow" /></button></td></tr>
                        @empty<tr><td colspan="4" class="empty-copy">* Esta bandeira ainda aguarda seus primeiros aventureiros.</td></tr>@endforelse
                    </tbody></table></div></section>
                </div>
                <div class="side-column">
                    <section class="expedition-panel panel" id="expedicoes" aria-labelledby="expedition-title"><div class="panel-heading"><h2><span class="section-number">03</span> EXPEDIÇÃO ALEATÓRIA</h2><x-pixel-icon name="dice" /></div><div class="expedition-content"><div class="portal-emblem" aria-hidden="true"><span class="portal-orbit"></span><x-pixel-icon name="door" /><span class="portal-spark portal-spark--one">+</span><span class="portal-spark portal-spark--two">+</span><span class="portal-spark portal-spark--three">·</span></div><h3 id="expedition-title">A masmorra chama.</h3><p class="expedition-intro">Você escolhe quem vai.<br>O destino escolhe o caminho.</p>
                        @if ($guilda && $guilda->aventureiros->isNotEmpty())
                        <form method="post" action="{{ route('guildas.sortear', $guilda) }}" data-expedition-form>@csrf<label class="field-label" for="expedition-member">ENVIAR AVENTUREIRO</label><div class="pixel-select"><select id="expedition-member" name="aventureiro_id" required>@foreach ($guilda->aventureiros as $member)<option value="{{ $member->id }}" @selected(($oferta['aventureiro_id'] ?? null) == $member->id)>{{ $member->nickname }} · NV. {{ $member->nivel }}</option>@endforeach</select><x-pixel-icon name="chevron" /></div><button type="submit" class="primary-action"><x-pixel-icon name="dice" /><span>SORTEAR DUNGEON</span><span>↗</span></button></form>
                        @else<p class="empty-copy">* Uma expedição precisa de um aventureiro nesta guilda.</p>@endif
                        @if ($oferta)
                        <div class="dungeon-offer" data-dungeon-offer tabindex="-1" @if(session('expedicao_sorteada')) data-new-offer @endif><p class="field-label">O DESTINO ESCOLHEU</p><h4>{{ $oferta['dungeon']['nome'] }}</h4><div class="offer-metadata"><span>{{ $oferta['dungeon']['dificuldade'] }}</span><strong>{{ $oferta['dungeon']['xp_recompensa'] }} XP</strong></div><p class="offer-hint">Recompensa potencial. Sortear um destino não altera o nível do aventureiro.</p></div>
                        @endif
                    </div><div class="expedition-footnote"><span>?</span><p>Alguns voltam com histórias.<br>Outros voltam mais fortes.</p></div></section>
                    <section class="chronicle-section" id="cronicas" aria-labelledby="chronicle-title"><div class="section-heading"><h2 id="chronicle-title"><span class="section-number">04</span> ECOS DO QUARTEL</h2><span class="section-caption">ÚLTIMOS REGISTROS</span></div><ol class="chronicle-list">@forelse ($historico as $quest)<li><span class="chronicle-marker {{ $quest->sucesso ? 'is-success' : '' }}" aria-label="{{ $quest->sucesso ? 'Sucesso' : 'Derrota' }}">{{ $quest->sucesso ? '+' : '×' }}</span><div><p><strong>{{ $quest->aventureiro?->nickname ?? 'Aventureiro removido' }}</strong> {{ $quest->sucesso ? 'retornou de' : 'foi derrotado em' }} <strong>{{ $quest->dungeon?->nome ?? 'Dungeon removida' }}</strong>.</p><span>{{ \Carbon\Carbon::parse($quest->data_quest)->format('d.m.Y') }} <i>/</i> {{ $quest->sucesso ? '+'.($quest->dungeon?->xp_recompensa ?? 0).' XP' : 'SEM RECOMPENSA' }}</span></div></li>@empty<li class="empty-copy">* O arquivo está em silêncio. A primeira história ainda será escrita.</li>@endforelse</ol></section>
                </div>
            </div>
            <footer class="dashboard-footer"><span><x-pixel-icon name="heart" /> A DETERMINAÇÃO É COLETIVA.</span><span>COROA DE CINZAS <i>©</i> 2026</span></footer>
        </main>
    </div>
    <dialog class="pixel-dialog guild-picker" aria-labelledby="guild-picker-title"><button class="dialog-close" type="button" data-close-dialog aria-label="Fechar"><x-pixel-icon name="close" /></button><p class="eyebrow">OUTRAS BANDEIRAS</p><h2 id="guild-picker-title">Escolha seu quartel.</h2><p class="dialog-description">Cada guilda tem seus próprios nomes e histórias.</p><div class="guild-list">@foreach ($guildas as $item)<a href="{{ route('guildas.dashboard', ['guilda' => $item->id]) }}" class="guild-option {{ $item->id === $guilda?->id ? 'is-current' : '' }}"><x-pixel-icon name="shield" /><span><strong>{{ $item->nome }}</strong><small>{{ $item->aventureiros_count }} MEMBROS · NÍVEL MÍNIMO {{ $item->nivel_minimo }}</small></span><span>{{ $item->id === $guilda?->id ? '◀' : '↗' }}</span></a>@endforeach</div></dialog>
    <dialog class="pixel-dialog member-dialog" aria-labelledby="member-dialog-title"><button class="dialog-close" type="button" data-close-dialog aria-label="Fechar"><x-pixel-icon name="close" /></button><p class="eyebrow">FICHA DO AVENTUREIRO</p><h2 id="member-dialog-title" data-detail-name></h2><dl class="member-details"><div><dt>CLASSE</dt><dd data-detail-class></dd></div><div><dt>NÍVEL</dt><dd data-detail-level></dd></div><div><dt>EXPEDIÇÕES</dt><dd data-detail-quests></dd></div><div><dt>GUILDA</dt><dd>{{ $guilda?->nome }}</dd></div></dl><button class="primary-action" type="button" data-choose-member>PREPARAR EXPEDIÇÃO <x-pixel-icon name="arrow" /></button></dialog>
    <div class="expedition-loading" hidden role="status" aria-live="polite"><div class="loading-sigil"><x-pixel-icon name="dice" /></div><p>CONSULTANDO O DESTINO<span>...</span></p><small>UM CAMINHO ESTÁ SE ABRINDO</small></div>
</body></html>
