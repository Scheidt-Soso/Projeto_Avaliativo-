set search_path to rpg_trabalho_bd;

-- function para ver qual nível o aventureiro ficaria de acordo com o xp dele
--(mas recebe somente uma quantidade de xp e não de um aventureiro especifico)

create or replace function fn_nivel_por_xp(p_xp bigint)
returns integer
language sql
immutable -- fazer p o mesmo XP sempre dar o mesmo nivel
strict --se receber null, retorna null anyway
parallel safe
as $$
    select least(1 + (greatest(p_xp, 0) / 1000)::integer, 100); --greatest impede que o xp seja negativo
$$;
