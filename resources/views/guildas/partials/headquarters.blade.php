<section class="headquarters panel" aria-labelledby="hq-title">
    <div class="panel-heading">
        <h2 id="hq-title"><span class="section-number">01</span> {{ $base['name'] }}</h2>
        <span class="live-label"><span></span> {{ $resumo['membros'] ? 'HÁ VIDA AQUI' : 'À ESPERA DE MEMBROS' }}</span>
    </div>
    <div class="scene-wrap">
        <canvas id="guild-scene" width="560" height="260" role="img"
            aria-label="{{ $base['name'] }}, em {{ $base['region'] }}, quartel de {{ $guilda?->nome ?? 'uma nova guilda' }} em pixel art"
            data-biome="{{ $base['biome'] }}" data-architecture="{{ $base['architecture'] }}"
            data-landmark="{{ $base['landmark'] }}" data-emblem="{{ $base['emblem'] }}"
            data-weather="{{ $base['weather'] }}" data-seed="{{ $base['seed'] }}"
            data-members="{{ $resumo['membros'] }}"></canvas>
        <div class="scene-coordinate">{{ $base['region'] }}</div>
        <span class="scene-corner scene-corner--tl"></span><span class="scene-corner scene-corner--br"></span>
    </div>
    <div class="dialogue-box">
        <x-pixel-icon name="star" />
        <p data-typewriter="{{ $base['message'] }}">{{ $base['message'] }}</p>
        <span class="dialogue-indicator" aria-hidden="true">▼</span>
    </div>
    <div class="hq-footer">
        <span><x-pixel-icon name="flag" /> {{ $base['region'] }}</span>
        <span>ENTRADA A PARTIR DO <b>NV. {{ str_pad($guilda?->nivel_minimo ?? 1, 2, '0', STR_PAD_LEFT) }}</b></span>
    </div>
</section>
