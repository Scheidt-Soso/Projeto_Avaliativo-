<x-auth-shell title="Registre seu nome." mode="register">
    <form class="auth-form" action="{{ route('register.store') }}" method="post" data-auth-form>
        @csrf
        <div class="auth-field-pair">
            <div>
                <label class="auth-label" for="nickname"><span>01</span> NICKNAME</label>
                <input class="auth-input" id="nickname" type="text" name="nickname" value="{{ old('nickname') }}" maxlength="50" autocomplete="username" placeholder="Como será lembrado?" required @error('nickname') aria-invalid="true" @enderror>
            </div>
            <div>
                <label class="auth-label" for="classe"><span>02</span> CLASSE</label>
                @if ($classes->isNotEmpty())
                    <select class="auth-input" id="classe" name="classe" required @error('classe') aria-invalid="true" @enderror>
                        <option value="">Seu caminho</option>
                        @foreach ($classes as $classe)<option value="{{ $classe }}" @selected(old('classe') === $classe)>{{ $classe }}</option>@endforeach
                    </select>
                @else
                    <input class="auth-input" id="classe" name="classe" value="{{ old('classe') }}" maxlength="50" required>
                @endif
            </div>
        </div>
        <label class="auth-label" for="senha"><span>03</span> SENHA <small>PELO MENOS 6 CARACTERES</small></label>
        <div class="auth-password">
            <input class="auth-input" id="senha" type="password" name="senha" minlength="6" maxlength="72" autocomplete="new-password" placeholder="Escolha uma palavra de acesso" required @error('senha') aria-invalid="true" @enderror>
            <button type="button" class="password-toggle" data-toggle-password="senha" aria-controls="senha" aria-label="Mostrar senha" aria-pressed="false" hidden>VER</button>
        </div>
        <label class="auth-label" for="senha-confirmation"><span>04</span> CONFIRMAR SENHA</label>
        <div class="auth-password">
            <input class="auth-input" id="senha-confirmation" type="password" name="senha_confirmation" minlength="6" maxlength="72" autocomplete="new-password" placeholder="Repita a palavra de acesso" required>
            <button type="button" class="password-toggle" data-toggle-password="senha-confirmation" aria-controls="senha-confirmation" aria-label="Mostrar confirmação da senha" aria-pressed="false" hidden>VER</button>
        </div>
        <button class="primary-action auth-submit" type="submit" data-submitting-label="REGISTRANDO..."><span>CRIAR AVENTUREIRO</span><x-pixel-icon name="arrow" /></button>
    </form>
    <div class="auth-alternative"><span>SEU NOME JÁ ESTÁ NO ARQUIVO?</span><a href="{{ route('login') }}">Voltar para entrar <span aria-hidden="true">↗</span></a></div>
</x-auth-shell>
