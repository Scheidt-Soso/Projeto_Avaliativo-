# Integração com o banco original

Estrutura conferida em consulta somente leitura em 02/11/2026. Nenhuma migration, criação de objeto ou alteração de registros foi executada durante esta conferência.

## Schema atual

- `rpg_trabalho_bd.guildas`: id, nome, nivel_minimo, data_fundacao.
- `rpg_trabalho_bd.aventureiros`: id, nickname, classe, nivel, guilda_id, senha, role.
- `rpg_trabalho_bd.dungeons`: id, nome, dificuldade, xp_recompensa.
- `rpg_trabalho_bd.quests`: id, aventureiro_id, **dungeon_id**, **data_quest**, sucesso.

O acesso usa `aventureiros.nickname` (único, até 50 caracteres), `senha` (até 255 caracteres, permite nulo) e `role` (obrigatório, padrão `jogador`, CHECK para `admin`/`jogador`). Na consulta havia 102 aventureiros sem senha e um com hash bcrypt. Nenhuma credencial foi criada, exposta ou redefinida durante a auditoria.

O PDF anterior usava `dungeons_id` e `data_exploracao`. A aplicação foi alinhada aos nomes atuais. A API de quests agora recebe e devolve `dungeon_id` e `data_quest`.

## Recursos encontrados

- `fn_total_xp(integer) -> bigint`: soma o XP das quests bem-sucedidas do aventureiro.
- `fn_nivel_por_xp(bigint) -> integer`: nível igual a `min(1 + max(xp, 0) / 1000, 100)`, com divisão inteira.
- `fn_atualizar_nivel_aventureiro(integer) -> integer`: recalcula e grava o nível.
- `pr_registrar_quest(integer, integer, date, boolean, OUT bigint, OUT integer)`: valida os parâmetros, registra a quest e retorna XP total e nível recalculado.
- `pr_vincular_aventureiro_guilda(integer, integer)`: verifica os registros e o nível mínimo antes de vincular o aventureiro à guilda.
- `trg_guildas_validar_nivel_minimo`: exige nível mínimo positivo e impede aumentos incompatíveis com os membros atuais.
- `trg_dungeons_proteger_recompensa`: impede alterar o XP de uma dungeon que já possui quests.
- Views de aventureiros/guildas, aventureiros inativos, dungeons de alto XP, histórico de sucesso e ranking materializado de XP.
- Índices de nível de aventureiro, dificuldade de dungeon, `quests (aventureiro_id, sucesso)` e `aventureiros (guilda_id)`.
- Não há rotina de dungeon aleatória ou cálculo de chance de sucesso no schema.

## Implementado no painel

Consultas de guildas, aventureiros, dungeons e quests. Histórico e XP são calculados a partir dos registros atuais. A dungeon é escolhida explicitamente entre os destinos cadastrados. A preparação guarda apenas IDs na sessão e não altera registros. O CRUD existente de quests apenas insere o registro; ele não aplica progressão automaticamente.

## Próxima feature: execução da expedição

Registrar aventureiro, dungeon escolhida, data e resultado informado, conforme os parâmetros de `pr_registrar_quest`. Revalidar membro/dungeon e evitar submissões duplicadas. Como o corpo das rotinas usa nomes sem schema, definir `SET LOCAL search_path TO rpg_trabalho_bd, public` na mesma transação antes do `CALL`. Evitar alterar o nível diretamente no controller ou inventar chances de vitória.

O recálculo do banco pode reduzir um nível antigo que não corresponda ao XP registrado: por isso a preparação atual não aciona essa atualização. Essa regra precisa ser apresentada com clareza ao conectar o registro da quest.

Os scripts das duas procedures, funções, triggers e índices foram integrados da `main`. A avaliação ainda exige evidências de uso, backup/restauração e documentação correspondente. A presença dos objetos no banco não conclui a integração de todos eles ao front.
