<x-app-shell :title="ucfirst($meta['singular']).' #'.$registro->id" :active="$recurso">
    <div class="catalog-back"><a class="text-link" href="{{ route('cadastros.'.$recurso.'.index') }}">← Voltar para {{ mb_strtolower($meta['titulo']) }}</a></div>
    <section class="catalog-heading"><div><p class="eyebrow">REGISTRO #{{ str_pad($registro->id, 3, '0', STR_PAD_LEFT) }}</p><h1>{{ $registro->nome ?? $registro->nickname ?? 'Quest #'.$registro->id }}</h1><p>{{ $meta['descricao'] }}</p></div>@can('gerenciar-cadastros')<a class="primary-action" href="{{ route('cadastros.'.$recurso.'.edit', $registro->id) }}"><span>Editar {{ $meta['singular'] }}</span><x-pixel-icon name="arrow" /></a>@endcan</section>
    <dl class="record-details panel">@foreach($meta['colunas'] as $campo => $label)<div><dt>{{ $label }}</dt><dd>{{ \App\Support\RecordDisplay::value($registro, $campo) }}</dd></div>@endforeach</dl>
    <div class="record-links">
        @if($recurso === 'guildas')<a class="secondary-action" href="{{ route('guildas.dashboard', ['guilda' => $registro->id]) }}">Abrir o quartel <x-pixel-icon name="flag" /></a><a class="secondary-action" href="{{ route('cadastros.aventureiros.index', ['guilda_id' => $registro->id]) }}">Ver membros <x-pixel-icon name="people" /></a>@endif
        @if($recurso === 'aventureiros')<a class="secondary-action" href="{{ route('cadastros.quests.index', ['aventureiro_id' => $registro->id]) }}">Todas as quests deste aventureiro <x-pixel-icon name="book" /></a>@endif
        @if($recurso === 'dungeons')<a class="secondary-action" href="{{ route('cadastros.quests.index', ['dungeon_id' => $registro->id]) }}">Quests desta dungeon <x-pixel-icon name="book" /></a>@endif
        @can('gerenciar-cadastros')
            @if(in_array($recurso, ['aventureiros', 'dungeons']))
                <a class="secondary-action" href="{{ route('cadastros.quests.create', [$recurso === 'aventureiros' ? 'aventureiro_id' : 'dungeon_id' => $registro->id]) }}">Registrar uma expedição <x-pixel-icon name="door" /></a>
            @endif
        @endcan
    </div>
    @if($recurso !== 'quests')
        <section class="record-history"><div class="section-heading"><h2>ÚLTIMAS EXPEDIÇÕES</h2><span class="section-caption">ATÉ 10 REGISTROS</span></div>
            @include('cadastros.history', ['quests' => $historico])
        </section>
    @endif
    @can('gerenciar-cadastros')
        <details class="record-danger"><summary>Excluir {{ $meta['singular'] }}</summary><div><h2>Confirme antes de excluir.</h2><p>{{ $impacto }}</p><p>Digite <strong>{{ $registro->nome ?? $registro->nickname ?? '#'.$registro->id }}</strong> para confirmar.</p><form method="post" action="{{ route('cadastros.'.$recurso.'.destroy', $registro->id) }}">@csrf @method('DELETE')<label class="sr-only" for="confirmacao">Nome do registro para confirmar a exclusão</label><input id="confirmacao" name="confirmacao" autocomplete="off" required><button class="secondary-action" type="submit">Excluir definitivamente <x-pixel-icon name="close" /></button></form></div></details>
    @endcan
</x-app-shell>
