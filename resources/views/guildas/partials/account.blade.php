<details class="account-menu">
    <summary aria-label="Conta de {{ auth()->user()->nickname }}"><x-pixel-icon name="people" /><span>CONTA</span></summary>
    <div class="account-popover">
        <p class="field-label">SEU REGISTRO</p>
        <strong>{{ auth()->user()->nickname }}</strong>
        <span>{{ auth()->user()->role === 'admin' ? 'Administrador' : 'Jogador' }} · {{ auth()->user()->classe }}</span>
        <a class="account-link" href="{{ route('perfil.show') }}">Meu aventureiro ↗</a>
        <a class="account-link" href="{{ route('relatorios.index') }}">Relatórios e ranking ↗</a>
        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="secondary-action">SAIR DO QUARTEL <x-pixel-icon name="arrow" /></button>
        </form>
    </div>
</details>
