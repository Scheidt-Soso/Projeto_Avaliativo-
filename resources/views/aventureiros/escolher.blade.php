<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Escolha seu aventureiro entre as cinzas de um reino esquecido. Um universo de guildas, dungeons e jornadas.">
    <title>Escolha seu aventureiro · Coroa de Cinzas</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#aventureiros">Pular para os aventureiros</a>
    <div class="world-backdrop" aria-hidden="true"></div>
    <header class="site-header">
        <a class="brand" href="{{ route('aventureiros.escolher') }}" aria-label="Coroa de Cinzas — início"><span class="brand-mark"><x-game-icon name="crown" /></span><span>COROA <span class="brand-of">DE</span> CINZAS<small>CRÔNICAS DE UM REINO ESQUECIDO</small></span></a>
        <nav class="journey-steps" aria-label="Etapas da jornada"><span class="journey-step is-current" aria-current="step"><b>01</b> Aventureiro</span><span class="step-line"></span><span class="journey-step"><b>02</b> Dungeon</span><span class="step-line"></span><span class="journey-step"><b>03</b> Expedição</span></nav>
        <button class="icon-button help-button" type="button" data-open-help aria-label="Sobre a escolha de aventureiro"><x-game-icon name="info" /></button>
    </header>
    <main class="page-shell">
        <section class="page-intro" aria-labelledby="page-title"><div><p class="eyebrow"><span></span> CAPÍTULO I · O CHAMADO</p><h1 id="page-title">A névoa aguarda.<br><em>Quem irá atravessá-la?</em></h1><p class="intro-copy">Nem toda lenda nasce de um rei. Algumas começam com um nome,<br class="desktop-break"> uma lâmina e a coragem de dar o primeiro passo.</p></div><div class="chapter-seal" aria-hidden="true"><span>Ⅰ</span><small>ESCOLHA SEU DESTINO</small></div></section>
        @if (session('sucesso'))<div class="notice notice--success" role="status"><x-game-icon name="check" /><span>{{ session('sucesso') }}</span></div>@endif
        @if ($errors->any())<div class="notice notice--error" role="alert"><x-game-icon name="info" /><span>{{ $errors->first() }}</span></div>@endif
        <div class="selection-layout">
            <section class="roster" id="aventureiros" aria-labelledby="roster-title">
                <div class="section-heading"><div><p class="eyebrow muted">OS QUE AINDA RESISTEM</p><h2 id="roster-title">Aventureiros <span>{{ $aventureiros->total() }}</span></h2></div><span class="roster-note"><span class="status-dot"></span> {{ $classes->count() }} classes disponíveis</span></div>
                <form class="filters" action="{{ route('aventureiros.escolher') }}" method="get" role="search">
                    <label class="search-field"><x-game-icon name="search" /><span class="sr-only">Buscar aventureiro pelo nome</span><input name="busca" type="search" placeholder="Procure um nome entre as cinzas..." maxlength="50" value="{{ $filtros['busca'] ?? '' }}"></label>
                    <label class="class-filter"><span class="sr-only">Filtrar por classe</span><select name="classe"><option value="">Todas as classes</option>@foreach ($classes as $classe)<option value="{{ $classe }}" @selected(($filtros['classe'] ?? '') === $classe)>{{ $classe }}</option>@endforeach</select></label>
                    <button class="filter-button" type="submit">Filtrar <x-game-icon name="arrow" /></button>
                </form>
                @if (!empty($filtros['busca']) || !empty($filtros['classe']))<div class="filter-summary"><span>{{ $aventureiros->total() }} resultado(s) para os filtros atuais</span><a href="{{ route('aventureiros.escolher') }}">Limpar filtros <x-game-icon name="close" /></a></div>@endif
                <div class="character-grid">
                    @forelse ($aventureiros as $aventureiro)
                        @php
                            $appearance = config('appearance.classes.'.$aventureiro->classe, []);
                            $active = $selecionado?->id === $aventureiro->id;
                        @endphp
                        <form class="character-card {{ $active ? 'is-selected' : '' }}" style="--class-color: {{ $appearance['color'] ?? '#b0a48f' }}" action="{{ route('aventureiros.selecionar') }}" method="post">
                            @csrf
                            <input type="hidden" name="aventureiro_id" value="{{ $aventureiro->id }}">
                            <button class="character-choice" type="submit" aria-label="{{ $active ? 'Selecionado:' : 'Escolher' }} {{ $aventureiro->nickname }}, {{ $aventureiro->classe }}, nível {{ $aventureiro->nivel }}">
                                <span class="card-top"><span class="class-label">{{ $aventureiro->classe }}</span><span class="level-badge"><small>NV.</small> {{ $aventureiro->nivel }}</span></span>
                                <span class="character-stage"><span class="character-halo"></span><x-adventurer-art :aventureiro="$aventureiro" /><span class="character-ground"></span></span>
                                <span class="character-caption"><strong>{{ $aventureiro->nickname }}</strong><span class="guild-label"><x-game-icon name="shield" />{{ $aventureiro->guilda?->nome ?? 'Sem guilda' }}</span></span>
                                <span class="card-action">{{ $active ? 'Aventureiro escolhido' : 'Escolher aventureiro' }}<x-game-icon :name="$active ? 'check' : 'arrow'" /></span>
                            </button>
                        </form>
                    @empty
                        <div class="empty-state"><x-game-icon name="search" /><h3>Nenhum nome respondeu ao chamado.</h3><p>Tente outro nome ou escolha uma classe diferente.</p><a class="text-link" href="{{ route('aventureiros.escolher') }}">Ver todos os aventureiros <x-game-icon name="arrow" /></a></div>
                    @endforelse
                </div>
                @if ($aventureiros->hasPages())
                    <nav class="pagination" aria-label="Páginas de aventureiros"><span>{{ $aventureiros->firstItem() }}–{{ $aventureiros->lastItem() }} de {{ $aventureiros->total() }} aventureiros</span><div>
                        @if ($aventureiros->onFirstPage())<span class="page-disabled">Anterior</span>@else<a href="{{ $aventureiros->previousPageUrl() }}" rel="prev">Anterior</a>@endif
                        <span class="page-number">{{ $aventureiros->currentPage() }} <small>/ {{ $aventureiros->lastPage() }}</small></span>
                        @if ($aventureiros->hasMorePages())<a href="{{ $aventureiros->nextPageUrl() }}" rel="next">Próxima <x-game-icon name="chevron" /></a>@else<span class="page-disabled">Próxima</span>@endif
                    </div></nav>
                @endif
            </section>
            <aside class="journey-sidebar" aria-labelledby="chosen-title"><div class="chosen-panel"><p class="eyebrow muted">SUA JORNADA</p><h2 id="chosen-title">{{ $selecionado ? 'O destino tem um nome.' : 'Uma história por escrever.' }}</h2>
                @if ($selecionado)
                    @php($chosenAppearance = config('appearance.classes.'.$selecionado->classe, []))
                    <div class="chosen-stage" style="--class-color: {{ $chosenAppearance['color'] ?? '#b0a48f' }}"><span class="chosen-orbit"></span><x-adventurer-art :aventureiro="$selecionado" size="featured" /></div>
                    <p class="chosen-class">{{ $selecionado->classe }} · Nível {{ $selecionado->nivel }}</p><h3 class="chosen-name">{{ $selecionado->nickname }}</h3><p class="chosen-epithet">{{ $chosenAppearance['epithet'] ?? 'Uma nova história entre as cinzas' }}</p>
                    <dl class="chosen-details"><div><dt>Guilda</dt><dd>{{ $selecionado->guilda?->nome ?? 'Sem guilda' }}</dd></div><div><dt>Registro</dt><dd>#{{ str_pad($selecionado->id, 3, '0', STR_PAD_LEFT) }}</dd></div></dl>
                    <p class="selection-status"><x-game-icon name="check" /> Escolha salva nesta sessão</p>
                @else
                    <div class="unchosen-symbol" aria-hidden="true"><x-game-icon name="sword" /><span>?</span></div><p class="unchosen-copy">Entre tantos nomes esquecidos,<br>um deles será lembrado.</p><p class="sidebar-hint">Escolha um aventureiro ao lado para começar sua história.</p>
                @endif
                <div class="sidebar-divider"><span></span><x-game-icon name="spark" /><span></span></div><div class="chapter-note"><x-game-icon name="book" /><div><strong>O primeiro passo</strong><p>A classe, o nível e a guilda acompanham seu aventureiro nas próximas etapas da jornada.</p></div></div>
            </div><blockquote class="world-quote">“Enquanto houver uma brasa,<br>a escuridão não será eterna.”<cite>— Inscrição no Portão das Cinzas</cite></blockquote></aside>
        </div>
    </main>
    <footer class="site-footer"><span><x-game-icon name="flame" /> COROA DE CINZAS</span><p>Cada nome, uma história. Cada escolha, um novo caminho.</p><small>CAPÍTULO I</small></footer>
    <dialog class="help-dialog" aria-labelledby="help-title"><button class="icon-button dialog-close" type="button" data-close-help aria-label="Fechar ajuda"><x-game-icon name="close" /></button><p class="eyebrow">ANTES DE PARTIR</p><h2 id="help-title">Escolha seu aventureiro</h2><p>Use a busca pelo nome ou filtre por uma das classes. Cada personagem mostra o nível e a guilda que já possui.</p><p>Ao escolher um aventureiro, ele fica salvo na sua sessão. Você pode trocar a escolha a qualquer momento nesta tela.</p><p class="help-footnote">Esta escolha não é um login e não altera os registros do personagem. A seleção de dungeon será a próxima etapa.</p><button class="primary-button" type="button" data-close-help>Entendido <x-game-icon name="arrow" /></button></dialog>
</body>
</html>
