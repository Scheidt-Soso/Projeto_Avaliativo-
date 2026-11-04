set search_path to rpg_trabalho_bd;

--trigger que protege a recompensa de uma dungeon se ela tiver quests registradas
create or replace function fn_trg_proteger_recompensa_dungeon()
returns trigger
language plpgsql
as $$
begin
    -- verifica se a recompensa foi alterada
    if new.xp_recompensa is distinct from old.xp_recompensa then

        -- verifica se a dungeon já tem quests registradas
        if exists (
            select 1
            from quests q
            where q.dungeon_id = old.id
        ) then
            raise exception
                'Não é possível alterar a recompensa de XP: esta dungeon já possui quests registradas.';
        end if;

    end if;

    return new;
end $$;

create trigger trg_dungeons_proteger_recompensa
before update of xp_recompensa
on dungeons
for each row
execute function fn_trg_proteger_recompensa_dungeon();
