@props(['aventureiro', 'size' => 'card'])
@php
    $appearance = config('appearance.classes.'.$aventureiro->classe);
    $portrait = config('appearance.characters.'.$aventureiro->id.'.portrait');
    $columns = config('appearance.columns', 5);
    $rows = config('appearance.rows', 2);
    $column = $appearance['column'] ?? 0;
    $row = $appearance['row'] ?? 0;
    $x = $appearance['x'] ?? ($columns > 1 ? $column * 100 / ($columns - 1) : 0);
@endphp
@if ($portrait)
    <img class="character-art character-art--{{ $size }} character-art--portrait" src="{{ asset($portrait) }}" alt="Retrato de {{ $aventureiro->nickname }}" loading="lazy">
@elseif ($appearance)
    <span class="character-art character-art--{{ $size }}" role="img" aria-label="{{ $aventureiro->classe }} em estilo pixel art"
        style="--sprite-url: url('{{ asset(config('appearance.atlas')) }}'); --sprite-columns: {{ $columns }}; --sprite-rows: {{ $rows }}; --sprite-x: {{ $x }}%; --sprite-y: {{ $rows > 1 ? $row * 100 / ($rows - 1) : 0 }}%"></span>
@else
    <span class="character-art character-art--{{ $size }} character-art--unknown" role="img" aria-label="Classe {{ $aventureiro->classe }}">{{ mb_substr($aventureiro->classe, 0, 1) }}</span>
@endif
