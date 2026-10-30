-- soma o xp de todas as quests de um aventureiro
create or replace function fn_total_xp(p_aventureiro_id integer)
returns bigint
language sql
stable --a função le, mas n altera registro
as $$
    select coalesce(sum(d.xp_recompensa), 0)::bigint
    from quests q
    join dungeons d on d.id = q.dungeon_id
    where q.aventureiro_id = p_aventureiro_id
      and q.sucesso = true;
$$;
