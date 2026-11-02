<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>registro</title>
</head>
<body>
    <form action="{{ route('register.store') }}" method="POST">
    @csrf

    <input type="text" name="nickname" placeholder="Nickname">

    <input type="text" name="classe" placeholder="Classe">

    <input type="password" name="senha" placeholder="Senha">

    <input type="password" name="senha_confirmation" placeholder="Confirmar senha">

    <button type="submit">Cadastrar</button>
</form>
</body>
</html>