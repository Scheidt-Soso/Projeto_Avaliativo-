<?php

return [
    'guildas' => [
        'titulo' => 'Guildas', 'singular' => 'guilda', 'acao' => 'Fundar guilda', 'icon' => 'flag',
        'descricao' => 'As bandeiras, seus membros e os requisitos para entrar.',
        'model' => App\Models\Guilda::class,
        'colunas' => ['nome' => 'Nome', 'nivel_minimo' => 'Nível mínimo', 'data_fundacao' => 'Fundação'],
        'campos' => [
            'nome' => ['label' => 'Nome da guilda', 'type' => 'text', 'max' => 100, 'required' => true],
            'nivel_minimo' => ['label' => 'Nível mínimo', 'type' => 'number', 'min' => 1, 'default' => 1, 'required' => true],
            'data_fundacao' => ['label' => 'Data de fundação', 'type' => 'date'],
        ],
    ],
    'aventureiros' => [
        'titulo' => 'Aventureiros', 'singular' => 'aventureiro', 'acao' => 'Cadastrar aventureiro', 'icon' => 'people',
        'descricao' => 'Nomes, classes, níveis e vínculos com as guildas.',
        'model' => App\Models\Aventureiro::class,
        'colunas' => ['nickname' => 'Nickname', 'classe' => 'Classe', 'nivel' => 'Nível', 'guilda.nome' => 'Guilda', 'role' => 'Papel'],
        'campos' => [
            'nickname' => ['label' => 'Nickname', 'type' => 'text', 'max' => 50, 'required' => true],
            'classe' => ['label' => 'Classe', 'type' => 'text', 'max' => 50, 'list' => 'classes', 'required' => true],
            'nivel' => ['label' => 'Nível', 'type' => 'number', 'min' => 1, 'default' => 1, 'required' => true],
            'guilda_id' => ['label' => 'Guilda', 'type' => 'select', 'options' => 'guildas', 'empty' => 'Sem guilda'],
            'role' => ['label' => 'Papel de acesso', 'type' => 'select', 'options' => 'roles', 'default' => 'jogador', 'required' => true],
        ],
    ],
    'dungeons' => [
        'titulo' => 'Dungeons', 'singular' => 'dungeon', 'acao' => 'Cadastrar dungeon', 'icon' => 'door',
        'descricao' => 'Os destinos conhecidos, suas dificuldades e recompensas.',
        'model' => App\Models\Dungeon::class,
        'colunas' => ['nome' => 'Nome', 'dificuldade' => 'Dificuldade', 'xp_recompensa' => 'Recompensa'],
        'campos' => [
            'nome' => ['label' => 'Nome da dungeon', 'type' => 'text', 'max' => 100, 'required' => true],
            'dificuldade' => ['label' => 'Dificuldade', 'type' => 'text', 'max' => 20, 'list' => 'dificuldades'],
            'xp_recompensa' => ['label' => 'Recompensa de XP', 'type' => 'number', 'min' => 0, 'required' => true],
        ],
    ],
    'quests' => [
        'titulo' => 'Quests', 'singular' => 'quest', 'acao' => 'Registrar quest', 'icon' => 'book',
        'descricao' => 'O histórico de expedições e seus resultados registrados.',
        'model' => App\Models\Quest::class,
        'colunas' => ['aventureiro.nickname' => 'Aventureiro', 'dungeon.nome' => 'Dungeon', 'data_quest' => 'Data', 'sucesso' => 'Resultado'],
        'campos' => [
            'aventureiro_id' => ['label' => 'Aventureiro', 'type' => 'select', 'options' => 'aventureiros', 'required' => true],
            'dungeon_id' => ['label' => 'Dungeon', 'type' => 'select', 'options' => 'dungeons', 'required' => true],
            'data_quest' => ['label' => 'Data da expedição', 'type' => 'date', 'required' => true],
            'sucesso' => ['label' => 'Resultado', 'type' => 'select', 'options' => 'resultados', 'required' => true],
        ],
    ],
];
