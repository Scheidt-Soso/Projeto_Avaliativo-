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

--testando o index idx_quests_aventureiro_sucesso
--inserindo 10.000 quests com ajuda da ia e dando rollback dps p não atualizar o nivel dos aventureiros em massa
set search_path to rpg_trabalho_bd;

begin;

with aventureiros_ids as materialized (
    select array_agg(id) as ids
    from rpg_trabalho_bd.aventureiros
),
dungeons_ids as materialized (
    select array_agg(id) as ids
    from rpg_trabalho_bd.dungeons
)
insert into rpg_trabalho_bd.quests (
    aventureiro_id,
    dungeon_id,
    data_quest,
    sucesso
)
select
    a.ids[1 + floor(random() * cardinality(a.ids))::integer],
    d.ids[1 + floor(random() * cardinality(d.ids))::integer],
    current_date - floor(random() * 365)::integer,
    random() < 0.7
from generate_series(1, 10000)
cross join aventureiros_ids a
cross join dungeons_ids d
where cardinality(a.ids) > 0
  and cardinality(d.ids) > 0;

analyze rpg_trabalho_bd.quests;

explain (analyze, buffers)
select aventureiro_id, sucesso
from rpg_trabalho_bd.quests
where aventureiro_id = 1
  and sucesso = true;

rollback;
