set search_path to rpg_trabalho_bd;

--function que atualiza o nível do aventureiro, usando as outras duas functions

create or replace function fn_atualizar_nivel_aventureiro(p_aventureiro_id integer)
returns integer
language plpgsql
as $$
declare
    v_nivel integer;
begin
    v_nivel := fn_nivel_por_xp(
        fn_total_xp(p_aventureiro_id)
    );

    update aventureiros
    set nivel = v_nivel
    where id = p_aventureiro_id;

    return v_nivel;
end;
$$;
