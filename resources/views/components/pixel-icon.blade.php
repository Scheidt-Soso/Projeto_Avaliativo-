@props(['name'])
<svg {{ $attributes->class(['pixel-icon']) }} viewBox="0 0 24 24" fill="currentColor" shape-rendering="crispEdges" aria-hidden="true">
@switch($name)
    @case('shield')<path d="M4 2h16v14h-2v3h-3v2h-2v2h-2v-2H9v-2H6v-3H4V2Zm2 2v11h2v3h3v2h2v-2h3v-3h2V4H6Z"/><path d="M11 6h2v3h3v2h-3v6h-2v-6H8V9h3V6Z"/>@break
    @case('flag')<path d="M4 2h2v2h14v12H6v6H4V2Zm2 4v8h12V6H6Zm3 2h6v4H9V8Z"/>@break
    @case('people')<path d="M9 2h6v6H9V2ZM7 10h10v3h2v8H5v-8h2v-3ZM2 5h4v5H2V5Zm16 0h4v5h-4V5ZM0 12h4v7H0v-7Zm20 0h4v7h-4v-7Z"/>@break
    @case('door')<path d="M8 2h8v2h3v3h2v15h-7v-7h-4v7H3V7h2V4h3V2Zm0 4v2H6v12h2v-7h8v7h2V8h-2V6H8Z"/>@break
    @case('book')<path d="M2 3h8v2h4V3h8v18h-8v2h-4v-2H2V3Zm2 2v14h6v-2h1V7h-1V5H4Zm10 0v2h-1v10h1v2h6V5h-6Z"/>@break
    @case('arrow')<path d="M13 4h2v2h2v2h2v2h2v4h-2v2h-2v2h-2v2h-2v-4h2v-2H2v-4h13V8h-2V4Z"/>@break
    @case('chevron')<path d="M5 8h4v2h6V8h4v4h-4v2h-2v2h-2v-2H9v-2H5V8Z"/>@break
    @case('star')<path d="M10 0h4v7h3V4h3v3h-3v3h7v4h-7v3h3v3h-3v-3h-3v7h-4v-7H7v3H4v-3h3v-3H0v-4h7V7H4V4h3v3h3V0Z"/>@break
    @case('dice')<path d="M3 1h18v2h2v18h-2v2H3v-2H1V3h2V1Zm2 3v16h14V4H5Z"/><path d="M7 6h3v3H7V6Zm7 0h3v3h-3V6Zm-4 4h3v3h-3v-3Zm-3 5h3v3H7v-3Zm7 0h3v3h-3v-3Z"/>@break
    @case('heart')<path d="M3 3h6v3h6V3h6v3h3v9h-3v3h-3v3h-3v3H9v-3H6v-3H3v-3H0V6h3V3Z"/>@break
    @case('pause')<path d="M5 3h5v18H5V3Zm9 0h5v18h-5V3Z"/>@break
    @case('play')<path d="M5 2h4v2h4v3h4v3h4v4h-4v3h-4v3H9v2H5V2Z"/>@break
    @case('close')<path d="M3 3h4v4h4v3h2V7h4V3h4v4h-4v4h-3v2h3v4h4v4h-4v-4h-4v-3h-2v3H7v4H3v-4h4v-4h3v-2H7V7H3V3Z"/>@break
    @case('check')<path d="M20 4h4v4h-4v4h-4v4h-4v4H8v-4H4v-4H0V8h4v4h4v4h4v-4h4V8h4V4Z"/>@break
    @case('search')<path d="M5 1h10v2h3v3h2v9h-3v3h-2v2H5v-2H2v-3H0V6h2V3h3V1Zm0 4v2H4v7h2v2h8v-2h2V7h-2V5H5Zm12 12h3v3h4v4h-4v-3h-3v-4Z"/>@break
@endswitch
</svg>
