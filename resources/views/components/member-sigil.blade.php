@props(['member'])
@if ($portrait = config('appearance.characters.'.$member->id.'.portrait'))
    <img class="member-portrait" src="{{ asset($portrait) }}" alt="Retrato de {{ $member->nickname }}" loading="lazy">
@else
    <svg {{ $attributes->class(['member-sigil']) }} viewBox="0 0 24 28" shape-rendering="crispEdges" aria-hidden="true">
        @if (in_array($member->classe, ['Mago', 'Bruxo', 'Clérigo', 'Druida']))
            <path fill="currentColor" d="M9 1h4v3h2v3h2v3H5V7h2V4h2V1ZM7 11h8v6h2v5h2v4H3v-4h2v-5h2v-6Z"/><path fill="#070707" d="M8 11h2v2H8v-2Zm4 0h2v2h-2v-2ZM10 19h2v7h-2v-7Z"/><path fill="currentColor" d="M21 5h2v21h-2V5Zm-2-3h6v4h-6V2Z"/>
        @elseif (in_array($member->classe, ['Ladino', 'Arqueiro', 'Bardo']))
            <path fill="currentColor" d="M8 2h7v2h2v8h-2v3h2v7h-3v5h-3v-6H9v6H6v-5H3v-7h3v-3H4V5h2V2h2Z"/><path fill="#070707" d="M7 7h8v5H7V7Z"/><path fill="currentColor" d="M8 8h2v2H8V8Zm5 0h2v2h-2V8ZM20 12h2v12h-2V12Z"/>
        @else
            <path fill="currentColor" d="M7 1h9v3h2v9h-2v3h3v9h-5v3h-3v-8H9v8H6v-3H2v-9h4v-3H4V4h3V1Z"/><path fill="#070707" d="M6 6h10v3H6V6Zm4 9h2v7h-2v-7Z"/><path fill="currentColor" d="M21 7h2v14h-2V7Zm-2 12h6v2h-6v-2Z"/>
        @endif
    </svg>
@endif
