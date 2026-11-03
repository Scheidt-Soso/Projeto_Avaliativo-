<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard do Administrador</title>
</head>
<body>
    <h1>Dashboard do Administrador</h1>

    <p>Bem-vindo, {{ auth()->user()->nickname }}!</p>

    <h2>Gerenciamento</h2>
    <ul>
        <li><a href="/api/admin/guildas">Guildas</a></li>
        <li><a href="/api/admin/aventureiros">Aventureiros</a></li>
        <li><a href="/api/admin/dungeons">Dungeons</a></li>
        <li><a href="/api/admin/quests">Quests</a></li>
    </ul>

    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit">Sair</button>
    </form>
</body>
</html>
