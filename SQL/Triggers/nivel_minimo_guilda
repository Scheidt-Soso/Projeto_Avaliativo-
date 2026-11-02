set search_path to rpg_trabalho_bd;

--criando a função da trigger para não deixar atualizar o nivel minimo da guilda se houver aventureiros com nivel menor
create or replace function fn_trg_validar_nivel_minimo_guilda()
returns trigger
language plpgsql
as $$
begin
    -- validar o nível mínimo
    if new.nivel_minimo is null or new.nivel_minimo < 1 then
        raise exception 'O nível mínimo da guilda deve ser maior ou igual a 1';
    end if;

    -- verificar se o aumento prejudica algum membro
    if tg_op = 'UPDATE'
       and new.nivel_minimo > old.nivel_minimo
       and exists (
            select 1
            from aventureiros a
            where a.guilda_id = new.id
              and (
                  a.nivel is null
                  or a.nivel < new.nivel_minimo
              )
       ) then

        raise exception
            'Não é possível aumentar o nível mínimo: existem aventureiros com nivel inferior ao requisito.';
    end if;

    return new;
end $$;

-- criando a trigger na tabela guildas
create or replace trigger trg_guildas_validar_nivel_minimo
before insert or update of nivel_minimo
on guildas
for each row
execute function fn_trg_validar_nivel_minimo_guilda();
