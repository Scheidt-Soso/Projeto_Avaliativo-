@props(['name'])
<svg {{ $attributes->class(['icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('crown') <path d="m3 6 4.5 4L12 3l4.5 7L21 6l-2 12H5L3 6ZM5 21h14"/> @break
        @case('sword') <path d="m14 4 6-1-1 6L8 20l-4-4L14 4ZM4 20l-2 2m3-10 7 7M14 7l3 3"/> @break
        @case('shield') <path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Z"/><path d="M12 7v9m-3-6h6"/> @break
        @case('search') <circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/> @break
        @case('arrow') <path d="M4 12h16m-6-6 6 6-6 6"/> @break
        @case('check') <path d="m5 12 4 4L19 6"/> @break
        @case('spark') <path d="m12 2 3 7 7 3-7 3-3 7-3-7-7-3 7-3 3-7Z"/> @break
        @case('book') <path d="M12 6v15m0-15C8 3 4 3 2 4v15c3-1 7-1 10 2 3-3 7-3 10-2V4c-2-1-6-1-10 2Z"/> @break
        @case('chevron') <path d="m9 5 7 7-7 7"/> @break
        @case('flame') <path d="M13 2c1 6-5 7-3 11 2 0 4-2 4-4 4 3 6 6 4 10-2 4-10 4-12 0-3-6 5-9 7-17Z"/> @break
        @case('close') <path d="m6 6 12 12M6 18 18 6"/> @break
        @case('info') <circle cx="12" cy="12" r="9"/><path d="M12 11v6m0-10v.1"/> @break
    @endswitch
</svg>
