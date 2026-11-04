--
-- PostgreSQL database dump
--

\restrict 6RQlHt3QGtNbKHkBAWv358qnAJzpNw3UeJnLIpQhvcDKkibkvZ5l0yFBzOzaCB7

-- Dumped from database version 18.6 (ac70818)
-- Dumped by pg_dump version 18.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: rpg_trabalho_bd; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA rpg_trabalho_bd;


--
-- Name: fn_atualizar_nivel_aventureiro(integer); Type: FUNCTION; Schema: rpg_trabalho_bd; Owner: -
--

CREATE FUNCTION rpg_trabalho_bd.fn_atualizar_nivel_aventureiro(p_aventureiro_id integer) RETURNS integer
    LANGUAGE plpgsql
    AS $$
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


--
-- Name: fn_nivel_por_xp(bigint); Type: FUNCTION; Schema: rpg_trabalho_bd; Owner: -
--

CREATE FUNCTION rpg_trabalho_bd.fn_nivel_por_xp(p_xp bigint) RETURNS integer
    LANGUAGE sql IMMUTABLE STRICT PARALLEL SAFE
    AS $$
    select least(1 + (greatest(p_xp, 0) / 1000)::integer, 100); --greatest impede que o xp seja negativo
$$;


--
-- Name: fn_total_xp(integer); Type: FUNCTION; Schema: rpg_trabalho_bd; Owner: -
--

CREATE FUNCTION rpg_trabalho_bd.fn_total_xp(p_aventureiro_id integer) RETURNS bigint
    LANGUAGE sql STABLE
    AS $$
    select coalesce(sum(d.xp_recompensa), 0)::bigint
    from quests q
    join dungeons d on d.id = q.dungeon_id
    where q.aventureiro_id = p_aventureiro_id
      and q.sucesso = true;
$$;


--
-- Name: fn_trg_proteger_recompensa_dungeon(); Type: FUNCTION; Schema: rpg_trabalho_bd; Owner: -
--

CREATE FUNCTION rpg_trabalho_bd.fn_trg_proteger_recompensa_dungeon() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
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


--
-- Name: fn_trg_validar_nivel_minimo_guilda(); Type: FUNCTION; Schema: rpg_trabalho_bd; Owner: -
--

CREATE FUNCTION rpg_trabalho_bd.fn_trg_validar_nivel_minimo_guilda() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
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


--
-- Name: pr_registrar_quest(integer, integer, date, boolean); Type: PROCEDURE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE PROCEDURE rpg_trabalho_bd.pr_registrar_quest(IN p_aventureiro_id integer, IN p_dungeon_id integer, IN p_data_quest date, IN p_sucesso boolean, OUT xp_total bigint, OUT nivel_atual integer)
    LANGUAGE plpgsql
    AS $$
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


--
-- Name: pr_vincular_aventureiro_guilda(integer, integer); Type: PROCEDURE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE PROCEDURE rpg_trabalho_bd.pr_vincular_aventureiro_guilda(IN p_aventureiro_id integer, IN p_guilda_id integer)
    LANGUAGE plpgsql
    AS $$
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


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: aventureiros; Type: TABLE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE TABLE rpg_trabalho_bd.aventureiros (
    id integer NOT NULL,
    nickname character varying(50) NOT NULL,
    classe character varying(50) NOT NULL,
    nivel integer DEFAULT 1,
    guilda_id integer,
    senha character varying(255),
    role character varying(20) DEFAULT 'jogador'::character varying NOT NULL,
    CONSTRAINT aventureiros_role_check CHECK (((role)::text = ANY ((ARRAY['admin'::character varying, 'jogador'::character varying])::text[])))
);


--
-- Name: aventureiros_id_seq; Type: SEQUENCE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE SEQUENCE rpg_trabalho_bd.aventureiros_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: aventureiros_id_seq; Type: SEQUENCE OWNED BY; Schema: rpg_trabalho_bd; Owner: -
--

ALTER SEQUENCE rpg_trabalho_bd.aventureiros_id_seq OWNED BY rpg_trabalho_bd.aventureiros.id;


--
-- Name: cache; Type: TABLE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE TABLE rpg_trabalho_bd.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE TABLE rpg_trabalho_bd.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: dungeons; Type: TABLE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE TABLE rpg_trabalho_bd.dungeons (
    id integer NOT NULL,
    nome character varying(100) NOT NULL,
    dificuldade character varying(20),
    xp_recompensa integer NOT NULL
);


--
-- Name: dungeons_id_seq; Type: SEQUENCE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE SEQUENCE rpg_trabalho_bd.dungeons_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: dungeons_id_seq; Type: SEQUENCE OWNED BY; Schema: rpg_trabalho_bd; Owner: -
--

ALTER SEQUENCE rpg_trabalho_bd.dungeons_id_seq OWNED BY rpg_trabalho_bd.dungeons.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE TABLE rpg_trabalho_bd.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE SEQUENCE rpg_trabalho_bd.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: rpg_trabalho_bd; Owner: -
--

ALTER SEQUENCE rpg_trabalho_bd.failed_jobs_id_seq OWNED BY rpg_trabalho_bd.failed_jobs.id;


--
-- Name: guildas; Type: TABLE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE TABLE rpg_trabalho_bd.guildas (
    id integer NOT NULL,
    nome character varying(100) NOT NULL,
    nivel_minimo integer DEFAULT 1,
    data_fundacao date
);


--
-- Name: guildas_id_seq; Type: SEQUENCE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE SEQUENCE rpg_trabalho_bd.guildas_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: guildas_id_seq; Type: SEQUENCE OWNED BY; Schema: rpg_trabalho_bd; Owner: -
--

ALTER SEQUENCE rpg_trabalho_bd.guildas_id_seq OWNED BY rpg_trabalho_bd.guildas.id;


--
-- Name: job_batches; Type: TABLE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE TABLE rpg_trabalho_bd.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE TABLE rpg_trabalho_bd.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE SEQUENCE rpg_trabalho_bd.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: rpg_trabalho_bd; Owner: -
--

ALTER SEQUENCE rpg_trabalho_bd.jobs_id_seq OWNED BY rpg_trabalho_bd.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE TABLE rpg_trabalho_bd.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE SEQUENCE rpg_trabalho_bd.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: rpg_trabalho_bd; Owner: -
--

ALTER SEQUENCE rpg_trabalho_bd.migrations_id_seq OWNED BY rpg_trabalho_bd.migrations.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE TABLE rpg_trabalho_bd.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: personal_access_tokens; Type: TABLE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE TABLE rpg_trabalho_bd.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE SEQUENCE rpg_trabalho_bd.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: rpg_trabalho_bd; Owner: -
--

ALTER SEQUENCE rpg_trabalho_bd.personal_access_tokens_id_seq OWNED BY rpg_trabalho_bd.personal_access_tokens.id;


--
-- Name: quests; Type: TABLE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE TABLE rpg_trabalho_bd.quests (
    id integer NOT NULL,
    aventureiro_id integer,
    dungeon_id integer,
    data_quest date CONSTRAINT quests_data_exploracao_not_null NOT NULL,
    sucesso boolean NOT NULL
);


--
-- Name: quests_id_seq; Type: SEQUENCE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE SEQUENCE rpg_trabalho_bd.quests_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: quests_id_seq; Type: SEQUENCE OWNED BY; Schema: rpg_trabalho_bd; Owner: -
--

ALTER SEQUENCE rpg_trabalho_bd.quests_id_seq OWNED BY rpg_trabalho_bd.quests.id;


--
-- Name: sessions; Type: TABLE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE TABLE rpg_trabalho_bd.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: users; Type: TABLE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE TABLE rpg_trabalho_bd.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: rpg_trabalho_bd; Owner: -
--

CREATE SEQUENCE rpg_trabalho_bd.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: rpg_trabalho_bd; Owner: -
--

ALTER SEQUENCE rpg_trabalho_bd.users_id_seq OWNED BY rpg_trabalho_bd.users.id;


--
-- Name: vw_matheusayres_aventureiros_guildas; Type: VIEW; Schema: rpg_trabalho_bd; Owner: -
--

CREATE VIEW rpg_trabalho_bd.vw_matheusayres_aventureiros_guildas AS
 SELECT a.nickname,
    a.classe,
    a.nivel,
    COALESCE(g.nome, 'Sem Guilda'::character varying) AS nome_guilda
   FROM (rpg_trabalho_bd.aventureiros a
     LEFT JOIN rpg_trabalho_bd.guildas g ON ((a.guilda_id = g.id)));


--
-- Name: vw_matheusayres_aventureiros_inativos; Type: VIEW; Schema: rpg_trabalho_bd; Owner: -
--

CREATE VIEW rpg_trabalho_bd.vw_matheusayres_aventureiros_inativos AS
 SELECT nickname,
    classe
   FROM rpg_trabalho_bd.aventureiros
  WHERE (NOT (id IN ( SELECT quests.aventureiro_id
           FROM rpg_trabalho_bd.quests)));


--
-- Name: vw_matheusayres_dungeons_alto_xp; Type: VIEW; Schema: rpg_trabalho_bd; Owner: -
--

CREATE VIEW rpg_trabalho_bd.vw_matheusayres_dungeons_alto_xp AS
 SELECT nome,
    dificuldade,
    xp_recompensa
   FROM rpg_trabalho_bd.dungeons
  WHERE ((xp_recompensa)::numeric > ( SELECT avg(dungeons_1.xp_recompensa) AS avg
           FROM rpg_trabalho_bd.dungeons dungeons_1));


--
-- Name: vw_matheusayres_historico_sucesso; Type: VIEW; Schema: rpg_trabalho_bd; Owner: -
--

CREATE VIEW rpg_trabalho_bd.vw_matheusayres_historico_sucesso AS
 SELECT q.data_quest,
    a.nickname,
    d.nome AS dungeon,
    d.xp_recompensa
   FROM ((rpg_trabalho_bd.quests q
     JOIN rpg_trabalho_bd.aventureiros a ON ((q.aventureiro_id = a.id)))
     JOIN rpg_trabalho_bd.dungeons d ON ((q.dungeon_id = d.id)))
  WHERE (q.sucesso = true);


--
-- Name: vw_matheusayres_ranking_xp; Type: MATERIALIZED VIEW; Schema: rpg_trabalho_bd; Owner: -
--

CREATE MATERIALIZED VIEW rpg_trabalho_bd.vw_matheusayres_ranking_xp AS
 SELECT a.nickname,
    sum(d.xp_recompensa) AS xp_total_ganho,
    count(q.id) AS quests_concluidas
   FROM ((rpg_trabalho_bd.aventureiros a
     JOIN rpg_trabalho_bd.quests q ON ((a.id = q.aventureiro_id)))
     JOIN rpg_trabalho_bd.dungeons d ON ((q.dungeon_id = d.id)))
  WHERE (q.sucesso = true)
  GROUP BY a.nickname
  ORDER BY (sum(d.xp_recompensa)) DESC
  WITH NO DATA;


--
-- Name: aventureiros id; Type: DEFAULT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.aventureiros ALTER COLUMN id SET DEFAULT nextval('rpg_trabalho_bd.aventureiros_id_seq'::regclass);


--
-- Name: dungeons id; Type: DEFAULT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.dungeons ALTER COLUMN id SET DEFAULT nextval('rpg_trabalho_bd.dungeons_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.failed_jobs ALTER COLUMN id SET DEFAULT nextval('rpg_trabalho_bd.failed_jobs_id_seq'::regclass);


--
-- Name: guildas id; Type: DEFAULT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.guildas ALTER COLUMN id SET DEFAULT nextval('rpg_trabalho_bd.guildas_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.jobs ALTER COLUMN id SET DEFAULT nextval('rpg_trabalho_bd.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.migrations ALTER COLUMN id SET DEFAULT nextval('rpg_trabalho_bd.migrations_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('rpg_trabalho_bd.personal_access_tokens_id_seq'::regclass);


--
-- Name: quests id; Type: DEFAULT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.quests ALTER COLUMN id SET DEFAULT nextval('rpg_trabalho_bd.quests_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.users ALTER COLUMN id SET DEFAULT nextval('rpg_trabalho_bd.users_id_seq'::regclass);


--
-- Name: aventureiros aventureiros_nickname_key; Type: CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.aventureiros
    ADD CONSTRAINT aventureiros_nickname_key UNIQUE (nickname);


--
-- Name: aventureiros aventureiros_pkey; Type: CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.aventureiros
    ADD CONSTRAINT aventureiros_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: dungeons dungeons_pkey; Type: CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.dungeons
    ADD CONSTRAINT dungeons_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: guildas guildas_pkey; Type: CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.guildas
    ADD CONSTRAINT guildas_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: quests quests_pkey; Type: CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.quests
    ADD CONSTRAINT quests_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: rpg_trabalho_bd; Owner: -
--

CREATE INDEX cache_expiration_index ON rpg_trabalho_bd.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: rpg_trabalho_bd; Owner: -
--

CREATE INDEX cache_locks_expiration_index ON rpg_trabalho_bd.cache_locks USING btree (expiration);


--
-- Name: idx_aventureiros_guilda_id; Type: INDEX; Schema: rpg_trabalho_bd; Owner: -
--

CREATE INDEX idx_aventureiros_guilda_id ON rpg_trabalho_bd.aventureiros USING btree (guilda_id);


--
-- Name: idx_matheusayres_dificuldade_dungeons; Type: INDEX; Schema: rpg_trabalho_bd; Owner: -
--

CREATE INDEX idx_matheusayres_dificuldade_dungeons ON rpg_trabalho_bd.dungeons USING btree (dificuldade);


--
-- Name: idx_matheusayres_nivel_aventureiros; Type: INDEX; Schema: rpg_trabalho_bd; Owner: -
--

CREATE INDEX idx_matheusayres_nivel_aventureiros ON rpg_trabalho_bd.aventureiros USING btree (nivel);


--
-- Name: idx_quests_aventureiro_sucesso; Type: INDEX; Schema: rpg_trabalho_bd; Owner: -
--

CREATE INDEX idx_quests_aventureiro_sucesso ON rpg_trabalho_bd.quests USING btree (aventureiro_id, sucesso);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: rpg_trabalho_bd; Owner: -
--

CREATE INDEX jobs_queue_index ON rpg_trabalho_bd.jobs USING btree (queue);


--
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: rpg_trabalho_bd; Owner: -
--

CREATE INDEX personal_access_tokens_expires_at_index ON rpg_trabalho_bd.personal_access_tokens USING btree (expires_at);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: rpg_trabalho_bd; Owner: -
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON rpg_trabalho_bd.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: rpg_trabalho_bd; Owner: -
--

CREATE INDEX sessions_last_activity_index ON rpg_trabalho_bd.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: rpg_trabalho_bd; Owner: -
--

CREATE INDEX sessions_user_id_index ON rpg_trabalho_bd.sessions USING btree (user_id);


--
-- Name: dungeons trg_dungeons_proteger_recompensa; Type: TRIGGER; Schema: rpg_trabalho_bd; Owner: -
--

CREATE TRIGGER trg_dungeons_proteger_recompensa BEFORE UPDATE OF xp_recompensa ON rpg_trabalho_bd.dungeons FOR EACH ROW EXECUTE FUNCTION rpg_trabalho_bd.fn_trg_proteger_recompensa_dungeon();


--
-- Name: guildas trg_guildas_validar_nivel_minimo; Type: TRIGGER; Schema: rpg_trabalho_bd; Owner: -
--

CREATE TRIGGER trg_guildas_validar_nivel_minimo BEFORE INSERT OR UPDATE OF nivel_minimo ON rpg_trabalho_bd.guildas FOR EACH ROW EXECUTE FUNCTION rpg_trabalho_bd.fn_trg_validar_nivel_minimo_guilda();


--
-- Name: aventureiros aventureiros_guilda_id_fkey; Type: FK CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.aventureiros
    ADD CONSTRAINT aventureiros_guilda_id_fkey FOREIGN KEY (guilda_id) REFERENCES rpg_trabalho_bd.guildas(id) ON DELETE SET NULL;


--
-- Name: quests quests_aventureiro_id_fkey; Type: FK CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.quests
    ADD CONSTRAINT quests_aventureiro_id_fkey FOREIGN KEY (aventureiro_id) REFERENCES rpg_trabalho_bd.aventureiros(id) ON DELETE CASCADE;


--
-- Name: quests quests_dungeons_id_fkey; Type: FK CONSTRAINT; Schema: rpg_trabalho_bd; Owner: -
--

ALTER TABLE ONLY rpg_trabalho_bd.quests
    ADD CONSTRAINT quests_dungeons_id_fkey FOREIGN KEY (dungeon_id) REFERENCES rpg_trabalho_bd.dungeons(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict 6RQlHt3QGtNbKHkBAWv358qnAJzpNw3UeJnLIpQhvcDKkibkvZ5l0yFBzOzaCB7

