<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
    <form action="{{ route('login.store') }}" method="POST">
        @csrf

        <input type="text" name="nickname" placeholder="Nickname">

        <input type="password" name="senha" placeholder="Senha">

        <button type="submit">Entrar</button>
    </form>
</body>
</html>