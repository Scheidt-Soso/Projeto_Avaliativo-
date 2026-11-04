<x-auth-shell title="Entre no quartel.">
    <form class="auth-form" action="{{ route('login.store') }}" method="post" data-auth-form>
        @csrf
        <label class="auth-label" for="nickname"><span>01</span> NICKNAME</label>
        <input class="auth-input" id="nickname" type="text" name="nickname" value="{{ old('nickname') }}" maxlength="50" autocomplete="username" placeholder="Seu nome nos registros" required @error('nickname') aria-invalid="true" @enderror>
        <label class="auth-label" for="senha"><span>02</span> SENHA</label>
        <div class="auth-password">
            <input class="auth-input" id="senha" type="password" name="senha" autocomplete="current-password" placeholder="Sua palavra de acesso" required @error('senha') aria-invalid="true" @enderror>
            <button type="button" class="password-toggle" data-toggle-password="senha" aria-controls="senha" aria-label="Mostrar senha" aria-pressed="false" hidden>VER</button>
        </div>
        <button class="primary-action auth-submit" type="submit" data-submitting-label="ENTRANDO..."><span>ENTRAR NO QUARTEL</span><x-pixel-icon name="arrow" /></button>
    </form>
    <div class="auth-alternative"><span>AINDA SEM UM REGISTRO?</span><a href="{{ route('register') }}">Crie seu aventureiro <span aria-hidden="true">↗</span></a></div>
</x-auth-shell>
