<dialog class="pixel-dialog dungeon-atlas" aria-labelledby="atlas-title" data-dungeon-atlas>
    <button class="dialog-close" type="button" data-close-dialog aria-label="Fechar atlas"><x-pixel-icon name="close" /></button>
    <p class="eyebrow">MAPAS ALÉM DA MURALHA</p>
    <h2 id="atlas-title">Atlas de expedições.</h2>
    <p class="dialog-description">Conheça os caminhos antes do sorteio. O próximo destino continua sendo aleatório.</p>
    <div class="atlas-guild"><x-pixel-icon name="flag" /><span>HISTÓRICO DE {{ $guilda?->nome ?? 'UMA NOVA GUILDA' }}</span><span>{{ $dungeons->count() }} DESTINOS</span></div>
    <form class="atlas-tools" data-atlas-tools hidden role="search" aria-label="Buscar dungeons">
        <label class="roster-search"><x-pixel-icon name="search" /><span class="sr-only">Nome da dungeon</span><input type="search" name="dungeon" maxlength="100" placeholder="Buscar nas anotações..." data-atlas-search></label>
        <label><span class="sr-only">Dificuldade da dungeon</span><select name="dificuldade" data-atlas-difficulty><option value="">Todas as dificuldades</option>@foreach ($dungeons->pluck('dificuldade')->filter()->unique()->sort() as $difficulty)<option value="{{ $difficulty }}">{{ $difficulty }}</option>@endforeach</select></label>
        <label><span class="sr-only">Ordenar dungeons por XP</span><select name="ordem" data-atlas-order><option value="asc">Menor XP primeiro</option><option value="desc">Maior XP primeiro</option></select></label>
        <label class="unexplored-toggle"><input type="checkbox" name="inexploradas" data-atlas-unexplored> Só não exploradas</label>
        <span class="atlas-count" role="status" aria-live="polite" data-atlas-count></span>
        <button class="atlas-reset" type="reset" data-atlas-reset>Limpar filtros</button>
    </form>
    <div class="dungeon-grid" data-atlas-list>
        @forelse ($dungeons as $dungeon)
            <article class="dungeon-entry" data-dungeon-entry data-dungeon-name="{{ $dungeon->nome }}" data-dungeon-difficulty="{{ $dungeon->dificuldade }}" data-dungeon-xp="{{ $dungeon->xp_recompensa }}" data-dungeon-attempts="{{ $dungeon->guild_attempts_count }}">
                <div class="dungeon-entry-header"><span>#{{ str_pad($dungeon->id, 3, '0', STR_PAD_LEFT) }}</span><span>{{ $dungeon->dificuldade ?: 'Não classificada' }}</span></div>
                <h3>{{ $dungeon->nome }}</h3>
                <p class="dungeon-reward">{{ number_format($dungeon->xp_recompensa, 0, ',', '.') }} <small>XP POTENCIAL</small></p>
                <div class="dungeon-record">
                    @if ($dungeon->guild_attempts_count)
                        <x-pixel-icon name="book" /><span>{{ $dungeon->guild_successes_count }} vitória(s) em {{ $dungeon->guild_attempts_count }} tentativa(s)</span>
                    @else
                        <x-pixel-icon name="door" /><span>Ainda não explorada pela guilda</span>
                    @endif
                </div>
            </article>
        @empty
            <p class="empty-copy">* O atlas está em branco. Ainda não há dungeons cadastradas.</p>
        @endforelse
        <p class="empty-copy atlas-empty" data-atlas-empty hidden>* Nenhum caminho corresponde aos filtros. Tente outra busca.</p>
    </div>
    <div class="atlas-footer"><p>Os registros contam as quests dos membros atuais. O histórico não garante o resultado da próxima expedição.</p><button class="secondary-action" type="button" data-close-dialog>VOLTAR AO QUARTEL <x-pixel-icon name="arrow" /></button></div>
</dialog>
