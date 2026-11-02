set search_path to rpg_trabalho_bd;

--index que busca as quests de um aventureiro, ajuda especialmente a função de xp
create index if not exists idx_quests_aventureiro_sucesso
on quests (aventureiro_id, sucesso);
