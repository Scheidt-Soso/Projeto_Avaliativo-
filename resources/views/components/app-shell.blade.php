@props(['title', 'active' => 'quartel', 'guilda' => null])
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Gerencie guildas, aventureiros e expedições em Coroa de Cinzas.">
    <title>{{ $title }} · Coroa de Cinzas</title>
    <link rel="preload" href="{{ Vite::asset('public/fonts/VT323-Regular.ttf') }}" as="font" type="font/ttf" crossorigin>
    @vite(['resources/css/guild.css', 'resources/js/guild.js'])
</head>
<body class="guild-app">
    <a class="skip-link" href="#conteudo">Ir ao conteúdo</a>
    <aside class="sidebar">
        <a class="wordmark" href="{{ route('guildas.dashboard') }}"><span class="wordmark-icon"><x-pixel-icon name="shield" /></span><span>COROA<br>DE CINZAS<small>ARQUIVO DAS GUILDAS</small></span></a>
        <div class="sidebar-label">SEU TERRITÓRIO</div>
        <nav class="main-nav catalog-nav" aria-label="Navegação principal">
            @foreach ([
                ['quartel', 'flag', 'Quartel', route('guildas.dashboard')],
                ['guildas', 'shield', 'Guildas', route('cadastros.guildas.index')],
                ['aventureiros', 'people', 'Aventureiros', route('cadastros.aventureiros.index')],
                ['dungeons', 'door', 'Dungeons', route('cadastros.dungeons.index')],
                ['quests', 'book', 'Quests', route('cadastros.quests.index')],
            ] as [$section, $icon, $label, $url])
                <a href="{{ $url }}" class="nav-item {{ $active === $section ? 'is-active' : '' }}" @if($active === $section) aria-current="page" @endif><x-pixel-icon :name="$icon" /><span>{{ $label }}</span></a>
            @endforeach
        </nav>
        <div class="sidebar-message"><span class="tiny-star">✦</span><p>Um nome pode cair.<br>Uma guilda permanece.</p></div>
        <div class="sidebar-bottom"><span class="connection-dot"></span> SESSÃO ATIVA</div>
    </aside>
    <div class="workspace">
        <header class="topbar">
            <a class="breadcrumb" href="{{ route('guildas.dashboard') }}">ARQUIVO <span>/</span> <b>{{ $title }}</b></a>
            <div class="topbar-actions">
                <button type="button" class="motion-toggle" aria-label="Pausar animações" aria-pressed="false" data-motion-toggle><x-pixel-icon name="pause" /><span>Pausar animações</span></button>
                @if (isset($actions)) {{ $actions }} @else <a class="guild-switch" href="{{ route('guildas.dashboard') }}"><x-pixel-icon name="flag" /><span>Meu quartel</span></a> @endif
                @include('guildas.partials.account')
            </div>
        </header>
        <main id="conteudo" tabindex="-1">
            @if (session('sucesso'))<div class="system-message" role="status"><x-pixel-icon name="check" /><p>{{ session('sucesso') }}</p></div>@endif
            @if ($errors->any())<div class="system-message error" role="alert"><x-pixel-icon name="close" /><div><strong>Confira os dados</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
            {{ $slot }}
            <footer class="dashboard-footer"><span><x-pixel-icon name="heart" /> A DETERMINAÇÃO É COLETIVA.</span><span>COROA DE CINZAS · 2026</span></footer>
        </main>
    </div>
</body>
</html>
