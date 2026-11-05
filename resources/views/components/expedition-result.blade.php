@props(['quest'])
<div class="expedition-outcome {{ $quest->sucesso ? 'is-success' : 'is-failure' }}" role="status" tabindex="-1" data-expedition-outcome>
    <span class="outcome-mark" aria-hidden="true">{{ $quest->sucesso ? '✦' : '×' }}</span>
    <p class="field-label">EXPEDIÇÃO CONCLUÍDA</p>
    <h3>{{ $quest->sucesso ? 'VOCÊ RETORNOU VITORIOSO.' : 'A DUNGEON VENCEU DESTA VEZ.' }}</h3>
    <p>{{ $quest->dungeon->nome }}</p>
    <div class="outcome-progress"><strong>{{ $quest->sucesso ? '+'.number_format($quest->dungeon->xp_recompensa, 0, ',', '.').' XP' : 'SEM XP NESTA TENTATIVA' }}</strong><span>NÍVEL {{ $quest->aventureiro->nivel }}</span></div>
    <a class="text-link" href="{{ route('cadastros.quests.show', $quest->id) }}">{{ $quest->sucesso ? '✓ Sucesso' : '× Falha' }} · Ver registro #{{ $quest->id }} ↗</a>
</div>
