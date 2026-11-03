<?php

return [
    // Aparência e classe são independentes. Uma futura skin pode usar outro atlas
    // ou um retrato próprio sem modificar o personagem no banco original.
    'atlas' => 'images/adventurer-classes.png',
    'columns' => 5,
    'rows' => 2,
    'classes' => [
        'Guerreiro' => ['column' => 0, 'row' => 0, 'x' => 1.5, 'color' => '#bc7961', 'epithet' => 'A lâmina que não se curva'],
        'Mago' => ['column' => 1, 'row' => 0, 'x' => 26.5, 'color' => '#7295b5', 'epithet' => 'O eco de um saber perdido'],
        'Arqueiro' => ['column' => 2, 'row' => 0, 'x' => 49.3, 'color' => '#91a17a', 'epithet' => 'Um olhar além da névoa'],
        'Paladino' => ['column' => 3, 'row' => 0, 'x' => 73.5, 'color' => '#c3ab70', 'epithet' => 'A última promessa de luz'],
        'Ladino' => ['column' => 4, 'row' => 0, 'color' => '#a294a9', 'epithet' => 'Um sussurro na escuridão'],
        'Clérigo' => ['column' => 0, 'row' => 1, 'color' => '#cfbd92', 'epithet' => 'A fé que sobrevive às cinzas'],
        'Bruxo' => ['column' => 1, 'row' => 1, 'color' => '#a28abd', 'epithet' => 'O preço de um pacto antigo'],
        'Druida' => ['column' => 2, 'row' => 1, 'color' => '#94a46b', 'epithet' => 'As raízes de um mundo ferido'],
        'Bardo' => ['column' => 3, 'row' => 1, 'color' => '#bb8187', 'epithet' => 'A canção que a noite esqueceu'],
        'Monge' => ['column' => 4, 'row' => 1, 'color' => '#c29d67', 'epithet' => 'O silêncio antes do golpe'],
    ],
    // Exemplo futuro: 42 => ['portrait' => 'images/portraits/pedro.webp'].
    // Caminhos relativos à pasta public. Nenhuma foto é enviada agora.
    'characters' => [],
];
