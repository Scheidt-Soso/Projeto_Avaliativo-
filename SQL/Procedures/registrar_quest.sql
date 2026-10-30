set search_path to rpg_trabalho_bd;

create or replace procedure pr_registrar_quest(
    in p_aventureiro_id integer,
    in p_dungeon_id integer,
    in p_data_quest date,
    in p_sucesso boolean,
    out xp_total bigint,
    out nivel_atual integer
)
language plpgsql
as $$
begin
    -- validar os parâmetros
    if p_aventureiro_id is null then
        raise exception 'É necessário informar o aventureiro';
    end if;

    if p_dungeon_id is null then
        raise exception 'É necessário informar a dungeon';
    end if;

    if p_data_quest is null then
        raise exception 'É necessário informar a data da quest';
    end if;

    if p_sucesso is null then
        raise exception 'É necessário informar o resultado da quest';
    end if;

    if p_data_quest > current_date then
        raise exception 'A data da quest não pode estar no futuro';
    end if;

    -- registra a quest
    insert into quests
        (aventureiro_id, dungeon_id, data_quest, sucesso)
    values
        (p_aventureiro_id, p_dungeon_id, p_data_quest, p_sucesso);

    -- calcula o XP total acumulado
    xp_total := fn_total_xp(
        p_aventureiro_id
    );

    -- atualiza o nível do aventureiro
    nivel_atual := fn_atualizar_nivel_aventureiro(
        p_aventureiro_id
    );

end;
$$;
