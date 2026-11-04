@if ($registros->hasPages())
    <nav class="catalog-pagination" aria-label="Paginação">
        @if ($registros->onFirstPage())<span aria-disabled="true">← Anterior</span>@else<a href="{{ $registros->previousPageUrl() }}" rel="prev">← Anterior</a>@endif
        <span>{{ $registros->firstItem() ?? 0 }}–{{ $registros->lastItem() ?? 0 }} de {{ $registros->total() }}</span>
        @if ($registros->hasMorePages())<a href="{{ $registros->nextPageUrl() }}" rel="next">Próxima →</a>@else<span aria-disabled="true">Próxima →</span>@endif
    </nav>
@endif
