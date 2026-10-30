# Integração com o banco original

Estrutura conferida em consulta somente leitura em 30/10/2026. Nenhuma migration, criação de objeto ou alteração de registros foi executada para o novo painel.

## Schema atual

- `rpg_trabalho_bd.guildas`: id, nome, nivel_minimo, data_fundacao.
- `rpg_trabalho_bd.aventureiros`: id, nickname, classe, nivel, guilda_id, senha, role.
- `rpg_trabalho_bd.dungeons`: id, nome, dificuldade, xp_recompensa.
- `rpg_trabalho_bd.quests`: id, aventureiro_id, **dungeon_id**, **data_quest**, sucesso.

O PDF anterior usava `dungeons_id` e `data_exploracao`. A aplicação foi alinhada aos nomes atuais. A API de quests agora recebe e devolve `dungeon_id` e `data_quest`.

## Recursos encontrados

- `fn_total_xp(integer) -> bigint`: soma o XP das quests bem-sucedidas do aventureiro.
- `fn_nivel_por_xp(bigint) -> integer`: nível igual a `min(1 + max(xp, 0) / 1000, 100)`, com divisão inteira.
- `fn_atualizar_nivel_aventureiro(integer) -> integer`: recalcula e grava o nível.
- `pr_registrar_quest(integer, integer, date, boolean, OUT bigint, OUT integer)`: valida os parâmetros, registra a quest e retorna XP total e nível recalculado.
- Views de aventureiros/guildas, aventureiros inativos, dungeons de alto XP, histórico de sucesso e ranking materializado de XP.
- Índices de nível de aventureiro e dificuldade de dungeon.
- Não foram encontradas triggers nem função de dungeon aleatória no schema na data da consulta.

## Implementado no painel

Consultas de guildas, aventureiros, dungeons e quests. Histórico e XP são calculados a partir dos registros atuais; o sorteio usa ordenação aleatória no banco via Eloquent e apenas salva a escolha na sessão. O CRUD existente de quests apenas insere o registro; ele não aplica progressão automaticamente.

## Próxima feature: execução da expedição

Definir a regra de sucesso, revalidar membro/dungeon, consumir uma oferta uma única vez e chamar a procedure em transação. Como o corpo das rotinas usa nomes sem schema, definir `SET LOCAL search_path TO rpg_trabalho_bd, public` na mesma transação antes do `CALL`. Evitar alterar o nível diretamente no controller.

O recálculo do banco pode reduzir um nível antigo que não corresponda ao XP registrado: por isso o sorteio atual não aciona essa atualização. Essa regra precisa ser apresentada com clareza ao conectar a execução.

A avaliação ainda requer completar a segunda procedure, duas triggers, demonstrações práticas, scripts SQL, backup/restauração e documentação correspondente. O painel inicial não representa a conclusão desses itens.
