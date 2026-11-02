@props(['title', 'mode' => 'login'])
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Entre no quartel de Coroa de Cinzas e acompanhe sua guilda.">
    <title>{{ $title }} · Coroa de Cinzas</title>
    <link rel="preload" href="{{ Vite::asset('public/fonts/VT323-Regular.ttf') }}" as="font" type="font/ttf" crossorigin>
    @vite(['resources/css/auth.css', 'resources/js/auth.js'])
</head>
<body class="auth-page">
    <a class="skip-link" href="#acesso">Ir ao formulário</a>
    <div class="auth-frame">
        <header class="auth-header">
            <a class="wordmark" href="{{ route('login') }}" aria-label="Coroa de Cinzas, entrada"><span class="wordmark-icon"><x-pixel-icon name="shield" /></span><span>COROA<br>DE CINZAS<small>ARQUIVO DAS GUILDAS</small></span></a>
            <span class="auth-edition">PORTÕES DO QUARTEL <span>/</span> {{ $mode === 'register' ? 'NOVO REGISTRO' : 'ACESSO' }}</span>
            <button type="button" class="motion-toggle" aria-pressed="false" data-auth-motion><x-pixel-icon name="pause" /><span>Pausar animações</span></button>
        </header>
        <main class="auth-content">
            <section class="auth-world" aria-label="Os portões do quartel">
                <div class="auth-world-caption"><span>ALÉM DA ÚLTIMA FOGUEIRA</span><span aria-hidden="true">✦</span></div>
                <div class="auth-art">
                    <canvas id="auth-scene" width="560" height="260" role="img" aria-label="Uma fortaleza sob a chuva, iluminada por tochas, em pixel art preto e branco" data-biome="mistlands" data-architecture="spire" data-landmark="ruins" data-emblem="cross" data-weather="rain" data-seed="2026" data-members="0"></canvas>
                    <span class="scene-corner scene-corner--tl"></span><span class="scene-corner scene-corner--br"></span>
                </div>
                <div class="auth-quote"><x-pixel-icon name="star" /><p>Um nome pode cair.<br><span>Uma guilda permanece.</span></p></div>
                <div class="auth-world-footer"><span>GUILDAS · AVENTUREIROS · EXPEDIÇÕES</span><span aria-hidden="true">[ I ]</span></div>
            </section>
            <section class="auth-entry" id="acesso" aria-labelledby="auth-title">
                <p class="eyebrow"><span class="square-dot"></span> {{ $mode === 'register' ? 'ABRA UM NOVO REGISTRO' : 'A BANDEIRA AINDA ESTÁ DE PÉ' }}</p>
                <h1 id="auth-title">{{ $title }}<span class="cursor-mark" aria-hidden="true">_</span></h1>
                <p class="auth-intro">{{ $mode === 'register' ? 'Seu primeiro capítulo começa no nível 1.' : 'Diga seu nome. Os portões se abrem.' }}</p>
                @if (session('sucesso'))
                    <div class="auth-feedback" role="status"><x-pixel-icon name="check" /><p>{{ session('sucesso') }}</p></div>
                @endif
                @if ($errors->any())
                    <div class="auth-feedback auth-feedback--error" role="alert"><x-pixel-icon name="close" /><p>{{ $errors->first() }}</p></div>
                @endif
                {{ $slot }}
            </section>
        </main>
        <footer class="auth-footer"><span><x-pixel-icon name="heart" /> A DETERMINAÇÃO É COLETIVA.</span><span>COROA DE CINZAS <span aria-hidden="true">/</span> 2026</span></footer>
    </div>
</body>
</html>
