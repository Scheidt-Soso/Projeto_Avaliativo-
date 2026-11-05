# Integração com o banco original

Estrutura conferida em consulta somente leitura em 03/11/2026. O backup foi restaurado em PostgreSQL local para testes; a auditoria e a restauração não alteraram o Neon.

## Schema atual

- `rpg_trabalho_bd.guildas`: id, nome, nivel_minimo, data_fundacao.
- `rpg_trabalho_bd.aventureiros`: id, nickname, classe, nivel, guilda_id, senha, role.
- `rpg_trabalho_bd.dungeons`: id, nome, dificuldade, xp_recompensa.
- `rpg_trabalho_bd.quests`: id, aventureiro_id, **dungeon_id**, **data_quest**, sucesso.

O acesso usa `aventureiros.nickname` (único, até 50 caracteres), `senha` (até 255 caracteres, permite nulo) e `role` (obrigatório, padrão `jogador`, CHECK para `admin`/`jogador`). Na consulta havia 105 aventureiros: 102 sem senha e três com hash bcrypt; um registro tinha papel administrativo. Nenhuma credencial foi criada, exposta ou redefinida durante a auditoria.

Nova conferência somente leitura em 04/11/2026: a conta administrativa continua com hash bcrypt cadastrado; as duas procedures, as funções e as restrições acima permanecem disponíveis. A senha administrativa existente não foi redefinida. Os testes de entrada usam contas temporárias na restauração local, revertidas ao final de cada teste.

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

CRUD web e API de guildas, aventureiros, dungeons e quests, com validação e permissões. A criação de quests chama a procedure original; editar/excluir quests ou excluir dungeons recalcula os níveis envolvidos e atualiza o ranking materializado. Vínculos de guilda passam pela procedure de nível mínimo. As triggers continuam sendo a autoridade para proteger nível mínimo e recompensas.

## Registro de expedições

Jogadores escolhem seu destino e usam **Explorar dungeon** para iniciar uma expedição. A aplicação sorteia sucesso/falha no servidor com probabilidade de 50% e envia esse resultado à procedure original. A data usa `CURRENT_DATE` do banco; o cliente não pode definir conta, resultado, data ou XP. A procedure registra a quest e aplica a progressão original. O sorteio fica na aplicação: o schema original não foi alterado para acrescentar regras de probabilidade.

Administradores mantêm o CRUD para corrigir o histórico. Tentativas de jogadores de registrar manualmente sucesso/falha pelos endpoints antigos continuam bloqueadas; o novo fluxo autenticado recebe somente dungeon e token. O token e o bloqueio de reenvio impedem novo sorteio e duplicação de XP para a mesma submissão. As rotinas usam nomes sem schema no corpo, por isso o service configura `SET LOCAL search_path TO rpg_trabalho_bd, public` na mesma transação antes do `CALL`.

O recálculo do banco pode reduzir um nível antigo que não corresponda ao XP registrado. O formulário informa que o nível será recalculado a partir do histórico completo; a preparação do destino continua sem alterar o nível.

## Backup e validação

Backup em formato custom restaurado em PostgreSQL local 18.6 em 03/11/2026. Contagens iguais na origem e na restauração: 15 guildas, 105 aventureiros, 20 dungeons e 204 quests. O dump real é local e ignorado pelo Git; o snapshot versionado contém somente a estrutura. A suíte PostgreSQL valida as duas procedures, as triggers, XP, ranking, cascatas e rollback.

Os scripts das duas procedures, funções, triggers e índices foram integrados da `main`. A avaliação ainda exige evidências de uso, backup/restauração e documentação correspondente. A presença dos objetos no banco não conclui a integração de todos eles ao front.
