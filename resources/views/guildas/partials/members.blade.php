<section class="members-section" id="membros" aria-labelledby="members-title" data-member-roster>
    <div class="section-heading">
        <h2 id="members-title"><span class="section-number">02</span> QUEM CARREGA A BANDEIRA</h2>
        <span class="section-caption">{{ $resumo['membros'] }} MEMBROS</span>
    </div>
    <form class="roster-tools" data-roster-tools hidden role="search" aria-label="Buscar membros da guilda">
        <label class="roster-search"><x-pixel-icon name="search" /><span class="sr-only">Nome do membro</span><input type="search" name="membro" maxlength="50" placeholder="Buscar um nome..." aria-controls="member-list" data-roster-search></label>
        <label><span class="sr-only">Classe do membro</span><select name="classe" aria-controls="member-list" data-roster-class><option value="">Todas as classes</option>@foreach (($guilda?->aventureiros ?? collect())->pluck('classe')->unique()->sort() as $class)<option value="{{ $class }}">{{ $class }}</option>@endforeach</select></label>
        <label><span class="sr-only">Ordenar membros</span><select name="ordem" aria-controls="member-list" data-roster-order><option value="nivel">Maior nível</option><option value="nome">Nome A–Z</option><option value="quests">Mais expedições</option></select></label>
        <div class="roster-filter-status"><span role="status" aria-live="polite" data-roster-count></span><button type="reset" data-roster-reset>Limpar</button></div>
    </form>
    <div class="member-table-wrap">
        <table class="member-table">
            <thead><tr><th>AVENTUREIRO</th><th>CLASSE</th><th>NÍVEL</th><th><span class="sr-only">Ações</span></th></tr></thead>
            <tbody id="member-list">
                @forelse ($guilda?->aventureiros ?? [] as $member)
                    <tr data-member-row data-roster-name="{{ $member->nickname }}" data-roster-class="{{ $member->classe }}" data-roster-level="{{ $member->nivel }}" data-roster-quests="{{ $member->quests_count ?? 0 }}">
                        <td><div class="member-identity"><span class="member-avatar"><x-member-sigil :member="$member" /></span><div><strong>{{ $member->nickname }}</strong><small>REGISTRO #{{ str_pad($member->id, 3, '0', STR_PAD_LEFT) }}</small></div></div></td>
                        <td><span class="class-tag">{{ $member->classe }}</span></td>
                        <td><span class="member-level">{{ str_pad($member->nivel, 2, '0', STR_PAD_LEFT) }}</span></td>
                        <td><button class="member-action" type="button" data-member-name="{{ $member->nickname }}" data-member-id="{{ $member->id }}" data-member-class="{{ $member->classe }}" data-member-level="{{ $member->nivel }}" data-member-quests="{{ $member->quests_count ?? 0 }}" aria-label="Ver ficha de {{ $member->nickname }}">FICHA <x-pixel-icon name="arrow" /></button></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty-copy">* Esta bandeira ainda aguarda seus primeiros aventureiros.</td></tr>
                @endforelse
                <tr data-roster-empty hidden><td colspan="4" class="empty-copy">* Nenhum membro corresponde à busca. Tente outro nome ou classe.</td></tr>
            </tbody>
        </table>
    </div>
</section>
