-- SQLS GERIAS UTILIZADOS NO TRABALHO

--utilizado para atualizar o nível em massa
set search_path to rpg_trabalho_bd;

DO $$
DECLARE
    v_aventureiro RECORD;
BEGIN
    FOR v_aventureiro IN
        SELECT id
        FROM aventureiros
    LOOP
        PERFORM fn_atualizar_nivel_aventureiro(
            v_aventureiro.id
        );
    END LOOP;
END $$;

-- testando a procedure registrar_quest
call pr_registrar_quest(
    1,
    2,
    current_date,
    true,
    null,
    null
);

select *
from rpg_trabalho_bd.quests
where aventureiro_id = 1
  and dungeon_id = 2
  and data_quest = current_date
  and sucesso = true;

-- testando a procedure vincular_aventureiro_guilda
call pr_vincular_aventureiro_guilda(5, 4);

--testando a trigger fn_trg_validar_nivel_minimo_guilda
update guildas
set nivel_minimo = 6
where id = 1;
--lançou exceção

--testando a trigger fn_trg_proteger_recompensa_dungeon
update dungeons
set xp_recompensa = xp_recompensa + 100
where id = 1;
--lançou exceção
