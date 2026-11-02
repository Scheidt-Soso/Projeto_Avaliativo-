set search_path to rpg_trabalho_bd;

--index que busca as quests de um aventureiro, ajuda especialmente a função de xp
create index if not exists idx_quests_aventureiro_sucesso
on quests (aventureiro_id, sucesso);

--index que busca os aventureiros de uma guilda, ajuda a função de validação de nivel minimo
create index if not exists idx_aventureiros_guilda_id
on aventureiros (guilda_id);
