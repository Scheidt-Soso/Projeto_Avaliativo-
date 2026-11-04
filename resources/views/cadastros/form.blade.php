<x-app-shell :title="($registro ? 'Editar ' : 'Nova ').$meta['singular']" :active="$recurso">
    <div class="catalog-back"><a class="text-link" href="{{ route('cadastros.'.$recurso.'.index') }}">← Voltar para {{ mb_strtolower($meta['titulo']) }}</a></div>
    <section class="catalog-heading"><div><p class="eyebrow">{{ $registro ? 'ATUALIZAR REGISTRO #'.$registro->id : 'ESCREVER UM NOVO CAPÍTULO' }}</p><h1>{{ $registro ? 'Editar '.$meta['singular'] : $meta['acao'] }}</h1><p>Os campos marcados com * são obrigatórios.</p></div></section>
    <form class="record-form panel" method="post" action="{{ $registro ? route('cadastros.'.$recurso.'.update', $registro->id) : route('cadastros.'.$recurso.'.store') }}" data-record-form>
        @csrf
        @if ($registro) @method('PUT') @else <input type="hidden" name="submission_token" value="{{ old('submission_token', (string) \Illuminate\Support\Str::uuid()) }}"> @endif
        <div class="record-form-fields">
            @foreach ($meta['campos'] as $name => $field)
                @php
                    $value = old($name, $valores[$name] ?? '');
                    if ($name === 'sucesso' && is_bool($value)) $value = (int) $value;
                @endphp
                <div class="record-field {{ in_array($name, ['nome', 'nickname']) ? 'record-field-wide' : '' }}">
                    <label for="field-{{ $name }}">{{ $field['label'] }} @if($field['required'] ?? false)<span aria-hidden="true">*</span><span class="sr-only">obrigatório</span>@endif</label>
                    @if ($field['type'] === 'select')
                        <select id="field-{{ $name }}" name="{{ $name }}" @required($field['required'] ?? false) @error($name) aria-invalid="true" aria-describedby="error-{{ $name }}" @enderror>
                            <option value="">{{ $field['empty'] ?? 'Escolha uma opção' }}</option>
                            @foreach ($opcoes[$field['options']] as $id => $label)<option value="{{ $id }}" @selected((string) $value === (string) $id)>{{ $label }}</option>@endforeach
                        </select>
                    @else
                        <input id="field-{{ $name }}" type="{{ $field['type'] }}" name="{{ $name }}" value="{{ $value }}" @required($field['required'] ?? false) @if(isset($field['max'])) maxlength="{{ $field['max'] }}" @endif @if(isset($field['min'])) min="{{ $field['min'] }}" max="2147483647" step="1" @endif @if(isset($field['list'])) list="suggest-{{ $name }}" @endif @if($recurso === 'quests' && $name === 'data_quest') max="{{ now()->toDateString() }}" @endif @if($name === 'xp_recompensa' && ($xpProtegido ?? false)) readonly aria-describedby="xp-help" @endif @error($name) aria-invalid="true" aria-describedby="error-{{ $name }}" @enderror>
                        @if(isset($field['list']))<datalist id="suggest-{{ $name }}">@foreach($opcoes[$field['list']] as $suggestion)<option value="{{ $suggestion }}">@endforeach</datalist>@endif
                    @endif
                    @error($name)<p class="field-error" id="error-{{ $name }}">{{ $message }}</p>@enderror
                </div>
            @endforeach
            @if ($recurso === 'aventureiros' && ! $registro)
                <div class="record-field"><label for="field-senha">Senha de acesso <small>opcional</small></label><input type="password" id="field-senha" name="senha" minlength="6" maxlength="72" autocomplete="new-password"><p class="field-help">Preencha para permitir login. Pelo menos 6 caracteres.</p></div>
                <div class="record-field"><label for="field-confirmation">Confirmar senha</label><input type="password" id="field-confirmation" name="senha_confirmation" autocomplete="new-password"></div>
            @endif
        </div>
        @if ($recurso === 'quests')<p class="record-note"><x-pixel-icon name="star" /> O resultado é informado no registro. O nível será recalculado com o XP de todas as quests bem-sucedidas do aventureiro.</p>@endif
        @if ($recurso === 'aventureiros')<p class="record-note"><x-pixel-icon name="flag" /> Para entrar em uma guilda, o aventureiro precisa alcançar o nível mínimo dela.</p>@endif
        @if ($xpProtegido ?? false)<p class="record-note" id="xp-help"><x-pixel-icon name="shield" /> A recompensa está protegida porque esta dungeon já tem quests registradas.</p>@endif
        <div class="record-form-actions"><a class="text-link" href="{{ $registro ? route('cadastros.'.$recurso.'.show', $registro->id) : route('cadastros.'.$recurso.'.index') }}">Cancelar</a><button class="primary-action" type="submit"><span>{{ $registro ? 'SALVAR ALTERAÇÕES' : 'CONFIRMAR REGISTRO' }}</span><x-pixel-icon name="check" /></button></div>
    </form>
</x-app-shell>
