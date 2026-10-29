<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Guilda dos Aventureiros')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alegreya+Sans:wght@400;500;700&family=Cinzel:wght@500;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body>
    <header class="site-header">
        <a href="{{ url('/') }}" class="brand">Guilda dos Aventureiros</a>

        {{-- Etapas seguintes: adicionar aqui os links conforme as telas forem criadas --}}
        @php
            $links = [
                ['label' => 'Início', 'href' => url('/'), 'active' => request()->is('/')],
            ];
        @endphp
        <nav class="site-nav">
            @foreach ($links as $link)
                <a href="{{ $link['href'] }}" @class(['is-active' => $link['active']])>{{ $link['label'] }}</a>
            @endforeach
        </nav>
    </header>

    <main class="site-main">
        @yield('content')
    </main>

    <footer class="site-footer">
        Projeto Avaliativo · Engenharia de Software · Campo Real
    </footer>

    @stack('scripts')
</body>
</html>
