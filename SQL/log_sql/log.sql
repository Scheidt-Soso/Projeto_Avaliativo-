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
END;
$$;

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
