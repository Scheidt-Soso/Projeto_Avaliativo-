set search_path to rpg_trabalho_bd;

create or replace procedure pr_vincular_aventureiro_guilda(
    in p_aventureiro_id integer,
    in p_guilda_id integer
)
language plpgsql
as $$
declare
    v_nivel_aventureiro integer;
    v_nivel_minimo integer;
begin
    -- valida os parametros
    if p_aventureiro_id is null then
        raise exception 'É preciso informar o aventureiro';
    end if;

    if p_guilda_id is null then
        raise exception 'É preciso informar a guilda';
    end if;

    -- consulta o nivel do aventureiro
    select a.nivel
    into v_nivel_aventureiro
    from aventureiros a
    where a.id = p_aventureiro_id;

    if not found then
        raise exception 'Aventureiro não encontrado';
    end if;

    -- consulta o nível minimo da guilda
    select g.nivel_minimo
    into v_nivel_minimo
    from guildas g
    where g.id = p_guilda_id;

    if not found then
        raise exception 'Guilda não encontrada';
    end if;

    -- verifica se o aventureiro tewm nivel
    if v_nivel_aventureiro is null
       or v_nivel_aventureiro < v_nivel_minimo then

        raise exception
            'Nivel insuficiente. Nível atual: %, nível minimo: %.',
            v_nivel_aventureiro, v_nivel_minimo;
    end if;

    -- aventureiro na guilda
    update aventureiros
    set guilda_id = p_guilda_id
    where id = p_aventureiro_id;

end $$;
