<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard do Jogador</title>
</head>
<body>
    <h1>Dashboard do Jogador</h1>

    <p>Bem-vindo, {{ auth()->user()->nickname }}!</p>
    <p>Classe: {{ auth()->user()->classe }}</p>
    <p>Nível: {{ auth()->user()->nivel }}</p>
    <p>Guilda: {{ auth()->user()->guilda?->nome ?? 'Sem guilda' }}</p>

    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit">Sair</button>
    </form>
</body>
</html>
