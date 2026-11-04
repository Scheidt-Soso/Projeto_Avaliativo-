<x-app-shell :title="$meta['titulo']" :active="$recurso">
    <section class="catalog-heading"><div><p class="eyebrow">ARQUIVO DO QUARTEL</p><h1>{{ $meta['titulo'] }}<span class="cursor-mark" aria-hidden="true">_</span></h1><p>{{ $meta['descricao'] }}</p></div>@can('gerenciar-cadastros')<a class="primary-action" href="{{ route('cadastros.'.$recurso.'.create') }}"><span>+ {{ $meta['acao'] }}</span><x-pixel-icon name="arrow" /></a>@endcan</section>
    <form class="catalog-filters" method="get" role="search" aria-label="Buscar {{ mb_strtolower($meta['titulo']) }}">
        <label class="catalog-search"><span>Buscar por nome</span><input type="search" name="busca" value="{{ $filtros['busca'] ?? '' }}" maxlength="100" placeholder="{{ $recurso === 'quests' ? 'Aventureiro ou dungeon' : 'Digite um nome' }}"></label>
        @if ($recurso === 'aventureiros')
            <label><span>Guilda</span><select name="guilda_id"><option value="">Todas as guildas</option>@foreach ($opcoes['guildas'] as $id => $nome)<option value="{{ $id }}" @selected(($filtros['guilda_id'] ?? '') == $id)>{{ $nome }}</option>@endforeach</select></label>
            <label><span>Classe</span><select name="classe"><option value="">Todas as classes</option>@foreach ($opcoes['classes'] as $classe)<option value="{{ $classe }}" @selected(($filtros['classe'] ?? '') === $classe)>{{ $classe }}</option>@endforeach</select></label>
        @elseif ($recurso === 'dungeons')
            <label><span>Dificuldade</span><select name="dificuldade"><option value="">Todas as dificuldades</option>@foreach ($opcoes['dificuldades'] as $dificuldade)<option value="{{ $dificuldade }}" @selected(($filtros['dificuldade'] ?? '') === $dificuldade)>{{ $dificuldade }}</option>@endforeach</select></label>
        @elseif ($recurso === 'quests')
            <label><span>Aventureiro</span><select name="aventureiro_id"><option value="">Todos os aventureiros</option>@foreach ($opcoes['aventureiros'] as $id => $nome)<option value="{{ $id }}" @selected(($filtros['aventureiro_id'] ?? '') == $id)>{{ $nome }}</option>@endforeach</select></label>
            <label><span>Resultado</span><select name="sucesso"><option value="">Todos os resultados</option>@foreach ($opcoes['resultados'] as $id => $nome)<option value="{{ $id }}" @selected(isset($filtros['sucesso']) && (string) $filtros['sucesso'] === (string) $id)>{{ $nome }}</option>@endforeach</select></label>
        @endif
        <button class="secondary-action" type="submit">Filtrar <x-pixel-icon name="search" /></button><a class="text-link" href="{{ route('cadastros.'.$recurso.'.index') }}">Limpar</a>
    </form>
    <div class="catalog-summary"><span>{{ $registros->total() }} registro(s) encontrado(s)</span><span>Página {{ $registros->currentPage() }} de {{ max(1, $registros->lastPage()) }}</span></div>
    <div class="catalog-table-wrap" tabindex="0" role="region" aria-label="Tabela de {{ mb_strtolower($meta['titulo']) }}">
        <table class="catalog-table"><thead><tr><th scope="col">Registro</th>@foreach ($meta['colunas'] as $label)<th scope="col">{{ $label }}</th>@endforeach<th scope="col">Ações</th></tr></thead><tbody>
            @forelse ($registros as $registro)
                <tr><td><span class="record-number">#{{ str_pad($registro->id, 3, '0', STR_PAD_LEFT) }}</span></td>@foreach ($meta['colunas'] as $campo => $label)<td>@if ($campo === 'sucesso')<span class="result-badge {{ $registro->sucesso ? 'is-success' : '' }}">{{ $registro->sucesso ? '✓ Sucesso' : '× Falha' }}</span>@else{{ \App\Support\RecordDisplay::value($registro, $campo) }}@endif</td>@endforeach<td><a class="table-link" href="{{ route('cadastros.'.$recurso.'.show', $registro->id) }}">Ver ficha <span aria-hidden="true">↗</span></a></td></tr>
            @empty<tr><td colspan="{{ count($meta['colunas']) + 2 }}"><div class="catalog-empty"><x-pixel-icon :name="$meta['icon']" /><h2>Nenhum registro encontrado.</h2><p>{{ request()->query() ? 'Ajuste os filtros para encontrar outros registros.' : 'O primeiro registro ainda será escrito.' }}</p></div></td></tr>@endforelse
        </tbody></table>
    </div>
    @include('cadastros.pagination')
</x-app-shell>
