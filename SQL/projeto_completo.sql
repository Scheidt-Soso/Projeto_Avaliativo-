-- COROA DE CINZAS - PROJETO BIMESTRAL BD II
-- Arquivo unico: estrutura, dados, funcoes, procedures, triggers, indices e views.
-- Exportacao consistente do schema original em 04/11/2026 (PostgreSQL 18.6 (ac70818)).
-- Dados: 15 guildas, 107 aventureiros, 20 dungeons, 207 quests.
--
-- COMO EXECUTAR
-- 1. Conecte a um banco PostgreSQL vazio, com permissao para criar o schema.
-- 2. Execute ESTE ARQUIVO INTEIRO no pgAdmin, DBeaver ou psql.
--    psql -v ON_ERROR_STOP=1 -d nome_do_banco_novo -f SQL/projeto_completo.sql
-- A criacao ocorre em uma transacao. Um schema existente causa erro, sem apagar dados.
--
-- DADOS DE ACESSO
-- Os registros de dominio preservam IDs, nomes, classes, niveis, vinculos e resultados.
-- Senhas de aventureiros ficam NULL; tokens, sessoes e dados internos de acesso nao sao copiados.
-- A estrutura das tabelas de suporte do Laravel/Sanctum tambem esta incluida.
-- Para usar o login nesta nova instalacao, o responsavel deve definir uma senha por hash
-- para a conta escolhida. Este arquivo nao modifica a senha do banco original.
--
-- Fontes: schema original e scripts em SQL/Functions, Procedures, Triggers e Index.
-- Os exemplos antigos que provocam erros ou alteram dados estao comentados no final.

BEGIN;
SET LOCAL client_encoding = 'UTF8';
SET LOCAL standard_conforming_strings = on;
SET LOCAL check_function_bodies = true;
SET search_path TO rpg_trabalho_bd, public;


-- ============================================================================
-- 1. SCHEMA E TABELAS (DOMINIO E SUPORTE LARAVEL/SANCTUM)
-- ============================================================================

-- rpg_trabalho_bd
CREATE SCHEMA rpg_trabalho_bd;

-- aventureiros
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

-- cache
CREATE TABLE rpg_trabalho_bd.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);

-- cache_locks
CREATE TABLE rpg_trabalho_bd.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);

-- dungeons
CREATE TABLE rpg_trabalho_bd.dungeons (
    id integer NOT NULL,
    nome character varying(100) NOT NULL,
    dificuldade character varying(20),
    xp_recompensa integer NOT NULL
);

-- failed_jobs
CREATE TABLE rpg_trabalho_bd.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);

-- guildas
CREATE TABLE rpg_trabalho_bd.guildas (
    id integer NOT NULL,
    nome character varying(100) NOT NULL,
    nivel_minimo integer DEFAULT 1,
    data_fundacao date
);

-- job_batches
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

-- jobs
CREATE TABLE rpg_trabalho_bd.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);

-- migrations
CREATE TABLE rpg_trabalho_bd.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);

-- password_reset_tokens
CREATE TABLE rpg_trabalho_bd.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);

-- personal_access_tokens
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

-- quests
CREATE TABLE rpg_trabalho_bd.quests (
    id integer NOT NULL,
    aventureiro_id integer,
    dungeon_id integer,
    data_quest date CONSTRAINT quests_data_exploracao_not_null NOT NULL,
    sucesso boolean NOT NULL
);

-- sessions
CREATE TABLE rpg_trabalho_bd.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);

-- users
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


-- ============================================================================
-- 2. SEQUENCES E VALORES PADRAO DOS IDENTIFICADORES
-- ============================================================================

-- aventureiros_id_seq
CREATE SEQUENCE rpg_trabalho_bd.aventureiros_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- dungeons_id_seq
CREATE SEQUENCE rpg_trabalho_bd.dungeons_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- failed_jobs_id_seq
CREATE SEQUENCE rpg_trabalho_bd.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- guildas_id_seq
CREATE SEQUENCE rpg_trabalho_bd.guildas_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- jobs_id_seq
CREATE SEQUENCE rpg_trabalho_bd.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- migrations_id_seq
CREATE SEQUENCE rpg_trabalho_bd.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- personal_access_tokens_id_seq
CREATE SEQUENCE rpg_trabalho_bd.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- quests_id_seq
CREATE SEQUENCE rpg_trabalho_bd.quests_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- users_id_seq
CREATE SEQUENCE rpg_trabalho_bd.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- aventureiros_id_seq
ALTER SEQUENCE rpg_trabalho_bd.aventureiros_id_seq OWNED BY rpg_trabalho_bd.aventureiros.id;

-- dungeons_id_seq
ALTER SEQUENCE rpg_trabalho_bd.dungeons_id_seq OWNED BY rpg_trabalho_bd.dungeons.id;

-- failed_jobs_id_seq
ALTER SEQUENCE rpg_trabalho_bd.failed_jobs_id_seq OWNED BY rpg_trabalho_bd.failed_jobs.id;

-- guildas_id_seq
ALTER SEQUENCE rpg_trabalho_bd.guildas_id_seq OWNED BY rpg_trabalho_bd.guildas.id;

-- jobs_id_seq
ALTER SEQUENCE rpg_trabalho_bd.jobs_id_seq OWNED BY rpg_trabalho_bd.jobs.id;

-- migrations_id_seq
ALTER SEQUENCE rpg_trabalho_bd.migrations_id_seq OWNED BY rpg_trabalho_bd.migrations.id;

-- personal_access_tokens_id_seq
ALTER SEQUENCE rpg_trabalho_bd.personal_access_tokens_id_seq OWNED BY rpg_trabalho_bd.personal_access_tokens.id;

-- quests_id_seq
ALTER SEQUENCE rpg_trabalho_bd.quests_id_seq OWNED BY rpg_trabalho_bd.quests.id;

-- users_id_seq
ALTER SEQUENCE rpg_trabalho_bd.users_id_seq OWNED BY rpg_trabalho_bd.users.id;

-- aventureiros id
ALTER TABLE ONLY rpg_trabalho_bd.aventureiros ALTER COLUMN id SET DEFAULT nextval('rpg_trabalho_bd.aventureiros_id_seq'::regclass);

-- dungeons id
ALTER TABLE ONLY rpg_trabalho_bd.dungeons ALTER COLUMN id SET DEFAULT nextval('rpg_trabalho_bd.dungeons_id_seq'::regclass);

-- failed_jobs id
ALTER TABLE ONLY rpg_trabalho_bd.failed_jobs ALTER COLUMN id SET DEFAULT nextval('rpg_trabalho_bd.failed_jobs_id_seq'::regclass);

-- guildas id
ALTER TABLE ONLY rpg_trabalho_bd.guildas ALTER COLUMN id SET DEFAULT nextval('rpg_trabalho_bd.guildas_id_seq'::regclass);

-- jobs id
ALTER TABLE ONLY rpg_trabalho_bd.jobs ALTER COLUMN id SET DEFAULT nextval('rpg_trabalho_bd.jobs_id_seq'::regclass);

-- migrations id
ALTER TABLE ONLY rpg_trabalho_bd.migrations ALTER COLUMN id SET DEFAULT nextval('rpg_trabalho_bd.migrations_id_seq'::regclass);

-- personal_access_tokens id
ALTER TABLE ONLY rpg_trabalho_bd.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('rpg_trabalho_bd.personal_access_tokens_id_seq'::regclass);

-- quests id
ALTER TABLE ONLY rpg_trabalho_bd.quests ALTER COLUMN id SET DEFAULT nextval('rpg_trabalho_bd.quests_id_seq'::regclass);

-- users id
ALTER TABLE ONLY rpg_trabalho_bd.users ALTER COLUMN id SET DEFAULT nextval('rpg_trabalho_bd.users_id_seq'::regclass);


-- ============================================================================
-- 3. CHAVES PRIMARIAS, UNICIDADE, CHECKS E CHAVES ESTRANGEIRAS
-- ============================================================================

-- aventureiros aventureiros_nickname_key
ALTER TABLE ONLY rpg_trabalho_bd.aventureiros
    ADD CONSTRAINT aventureiros_nickname_key UNIQUE (nickname);

-- aventureiros aventureiros_pkey
ALTER TABLE ONLY rpg_trabalho_bd.aventureiros
    ADD CONSTRAINT aventureiros_pkey PRIMARY KEY (id);

-- cache_locks cache_locks_pkey
ALTER TABLE ONLY rpg_trabalho_bd.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);

-- cache cache_pkey
ALTER TABLE ONLY rpg_trabalho_bd.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);

-- dungeons dungeons_pkey
ALTER TABLE ONLY rpg_trabalho_bd.dungeons
    ADD CONSTRAINT dungeons_pkey PRIMARY KEY (id);

-- failed_jobs failed_jobs_pkey
ALTER TABLE ONLY rpg_trabalho_bd.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);

-- failed_jobs failed_jobs_uuid_unique
ALTER TABLE ONLY rpg_trabalho_bd.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);

-- guildas guildas_pkey
ALTER TABLE ONLY rpg_trabalho_bd.guildas
    ADD CONSTRAINT guildas_pkey PRIMARY KEY (id);

-- job_batches job_batches_pkey
ALTER TABLE ONLY rpg_trabalho_bd.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);

-- jobs jobs_pkey
ALTER TABLE ONLY rpg_trabalho_bd.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);

-- migrations migrations_pkey
ALTER TABLE ONLY rpg_trabalho_bd.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);

-- password_reset_tokens password_reset_tokens_pkey
ALTER TABLE ONLY rpg_trabalho_bd.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);

-- personal_access_tokens personal_access_tokens_pkey
ALTER TABLE ONLY rpg_trabalho_bd.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);

-- personal_access_tokens personal_access_tokens_token_unique
ALTER TABLE ONLY rpg_trabalho_bd.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);

-- quests quests_pkey
ALTER TABLE ONLY rpg_trabalho_bd.quests
    ADD CONSTRAINT quests_pkey PRIMARY KEY (id);

-- sessions sessions_pkey
ALTER TABLE ONLY rpg_trabalho_bd.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);

-- users users_email_unique
ALTER TABLE ONLY rpg_trabalho_bd.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);

-- users users_pkey
ALTER TABLE ONLY rpg_trabalho_bd.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);

-- aventureiros aventureiros_guilda_id_fkey
ALTER TABLE ONLY rpg_trabalho_bd.aventureiros
    ADD CONSTRAINT aventureiros_guilda_id_fkey FOREIGN KEY (guilda_id) REFERENCES rpg_trabalho_bd.guildas(id) ON DELETE SET NULL;

-- quests quests_aventureiro_id_fkey
ALTER TABLE ONLY rpg_trabalho_bd.quests
    ADD CONSTRAINT quests_aventureiro_id_fkey FOREIGN KEY (aventureiro_id) REFERENCES rpg_trabalho_bd.aventureiros(id) ON DELETE CASCADE;

-- quests quests_dungeons_id_fkey
ALTER TABLE ONLY rpg_trabalho_bd.quests
    ADD CONSTRAINT quests_dungeons_id_fkey FOREIGN KEY (dungeon_id) REFERENCES rpg_trabalho_bd.dungeons(id) ON DELETE CASCADE;


-- ============================================================================
-- 4. DADOS DO BANCO ORIGINAL (SEM SENHAS OU TOKENS)
-- ============================================================================

-- guildas: 15 registros
INSERT INTO rpg_trabalho_bd.guildas (id, nome, nivel_minimo, data_fundacao) VALUES
    (1, 'Lâminas Rubras', 5, '2020-01-15'),
    (2, 'Corvos de Ônix', 10, '2018-05-20'),
    (3, 'Magos da Prata', 15, '2015-11-03'),
    (4, 'Escudos de Carvalho', 1, '2022-02-10'),
    (5, 'Rosa de Ferro', 20, '2019-08-25'),
    (6, 'Guardiões do Alvorecer', 30, '2010-12-12'),
    (7, 'Filhos de Yggdrasil', 5, '2021-04-18'),
    (8, 'Sombras Silenciosas', 25, '2017-07-07'),
    (9, 'Dragões Celestiais', 40, '2005-09-30'),
    (10, 'Presas de Gelo', 10, '2020-10-31'),
    (11, 'Ordem do Grifo', 15, '2016-03-14'),
    (12, 'A Mão Oculta', 35, '2012-06-06'),
    (13, 'Vanguarda de Aço', 5, '2023-01-01'),
    (14, 'Confraria do Abismo', 50, '2000-01-01'),
    (15, 'Portadores da Luz', 20, '2014-09-21');

SELECT pg_catalog.setval('rpg_trabalho_bd.guildas_id_seq', 15, TRUE);

-- aventureiros: 107 registros
INSERT INTO rpg_trabalho_bd.aventureiros (id, nickname, classe, nivel, guilda_id, role) VALUES
    (1, 'Kaelthas', 'Guerreiro', 4, 1, 'jogador'),
    (2, 'Lyriel', 'Mago', 5, 2, 'jogador'),
    (3, 'Thogar', 'Guerreiro', 6, 3, 'jogador'),
    (4, 'Elara', 'Clérigo', 3, 4, 'jogador'),
    (5, 'Grimnir', 'Paladino', 1, 4, 'jogador'),
    (6, 'Valeria', 'Ladino', 11, 6, 'jogador'),
    (7, 'Xandor', 'Bruxo', 51, 7, 'jogador'),
    (8, 'Nyx', 'Ladino', 4, 8, 'jogador'),
    (9, 'Faelin', 'Arqueiro', 4, 9, 'jogador'),
    (10, 'Zyrus', 'Mago', 1, NULL, 'jogador'),
    (11, 'Aelar', 'Druida', 28, 11, 'jogador'),
    (12, 'Belmont', 'Guerreiro', 4, 12, 'jogador'),
    (13, 'Corvin', 'Bardo', 1, 13, 'jogador'),
    (14, 'Draken', 'Paladino', 57, 14, 'jogador'),
    (15, 'Eilistra', 'Clérigo', 1, NULL, 'jogador'),
    (16, 'Fenris', 'Guerreiro', 19, 1, 'jogador'),
    (17, 'Gorm', 'Monge', 3, 2, 'jogador'),
    (18, 'Haldor', 'Bruxo', 1, 3, 'jogador'),
    (19, 'Ignis', 'Mago', 6, 4, 'jogador'),
    (20, 'Jorah', 'Guerreiro', 1, NULL, 'jogador'),
    (21, 'Kaelen', 'Arqueiro', 1, 6, 'jogador'),
    (22, 'Lirien', 'Druida', 6, 7, 'jogador'),
    (23, 'Maiev', 'Ladino', 1, 8, 'jogador'),
    (24, 'Nox', 'Bruxo', 3, 9, 'jogador'),
    (25, 'Orion', 'Mago', 1, NULL, 'jogador'),
    (26, 'Phaedra', 'Clérigo', 11, 11, 'jogador'),
    (27, 'Qilby', 'Bardo', 57, 12, 'jogador'),
    (28, 'Rurik', 'Guerreiro', 1, 13, 'jogador'),
    (29, 'Sylas', 'Monge', 4, 14, 'jogador'),
    (30, 'Talon', 'Ladino', 1, NULL, 'jogador'),
    (31, 'Ulfric', 'Paladino', 28, 1, 'jogador'),
    (32, 'Vex', 'Arqueiro', 4, 2, 'jogador'),
    (33, 'Wynter', 'Mago', 1, 3, 'jogador'),
    (34, 'Xerxes', 'Bruxo', 57, 4, 'jogador'),
    (35, 'Yvaine', 'Clérigo', 1, NULL, 'jogador'),
    (36, 'Zephyr', 'Monge', 19, 6, 'jogador'),
    (37, 'Aldric', 'Guerreiro', 3, 7, 'jogador'),
    (38, 'Beren', 'Druida', 1, 8, 'jogador'),
    (39, 'Cael', 'Bardo', 6, 9, 'jogador'),
    (40, 'Darius', 'Paladino', 1, NULL, 'jogador'),
    (41, 'Elandra', 'Arqueiro', 1, 11, 'jogador'),
    (42, 'Finn', 'Ladino', 6, 12, 'jogador'),
    (43, 'Gale', 'Mago', 1, 13, 'jogador'),
    (44, 'Hestia', 'Clérigo', 3, 14, 'jogador'),
    (45, 'Ikaris', 'Guerreiro', 1, NULL, 'jogador'),
    (46, 'Jax', 'Monge', 11, 1, 'jogador'),
    (47, 'Kaelia', 'Bruxo', 57, 2, 'jogador'),
    (48, 'Lucian', 'Paladino', 1, 3, 'jogador'),
    (49, 'Mira', 'Ladino', 4, 4, 'jogador'),
    (50, 'Nero', 'Bardo', 1, NULL, 'jogador'),
    (51, 'Orik', 'Guerreiro', 28, 6, 'jogador'),
    (52, 'Pyke', 'Ladino', 4, 7, 'jogador'),
    (53, 'Quinn', 'Arqueiro', 1, 8, 'jogador'),
    (54, 'Rowan', 'Druida', 57, 9, 'jogador'),
    (55, 'Soren', 'Mago', 1, NULL, 'jogador'),
    (56, 'Thalia', 'Clérigo', 19, 11, 'jogador'),
    (57, 'Uri', 'Monge', 3, 12, 'jogador'),
    (58, 'Vance', 'Bruxo', 1, 13, 'jogador'),
    (59, 'Wren', 'Bardo', 6, 14, 'jogador'),
    (60, 'Xavi', 'Guerreiro', 1, NULL, 'jogador'),
    (61, 'Yara', 'Arqueiro', 1, 1, 'jogador'),
    (62, 'Zane', 'Ladino', 6, 2, 'jogador'),
    (63, 'Aria', 'Mago', 1, 3, 'jogador'),
    (64, 'Bran', 'Paladino', 3, 4, 'jogador'),
    (65, 'Cora', 'Clérigo', 1, NULL, 'jogador'),
    (66, 'Dante', 'Bruxo', 11, 6, 'jogador'),
    (67, 'Ezra', 'Monge', 57, 2, 'jogador'),
    (68, 'Fiona', 'Druida', 1, 8, 'jogador'),
    (69, 'Gael', 'Guerreiro', 4, 9, 'jogador'),
    (70, 'Hugo', 'Bardo', 1, NULL, 'jogador'),
    (71, 'Iris', 'Arqueiro', 28, 11, 'jogador'),
    (72, 'Jace', 'Mago', 4, 12, 'jogador'),
    (73, 'Kira', 'Ladino', 1, 13, 'jogador'),
    (74, 'Leon', 'Paladino', 57, 14, 'jogador'),
    (75, 'Mila', 'Clérigo', 1, NULL, 'jogador'),
    (76, 'Nova', 'Bruxo', 19, 1, 'jogador'),
    (77, 'Omar', 'Guerreiro', 3, 2, 'jogador'),
    (78, 'Piper', 'Bardo', 1, 3, 'jogador'),
    (79, 'Quinlan', 'Druida', 6, 4, 'jogador'),
    (80, 'Reed', 'Monge', 1, NULL, 'jogador'),
    (81, 'Sage', 'Mago', 1, 6, 'jogador'),
    (82, 'Titus', 'Paladino', 6, 7, 'jogador'),
    (83, 'Una', 'Clérigo', 1, 8, 'jogador'),
    (84, 'Vera', 'Arqueiro', 3, 9, 'jogador'),
    (85, 'Wyatt', 'Ladino', 1, NULL, 'jogador'),
    (86, 'Xander', 'Guerreiro', 11, 11, 'jogador'),
    (87, 'Yuri', 'Bruxo', 57, 12, 'jogador'),
    (88, 'Zara', 'Druida', 1, 13, 'jogador'),
    (89, 'Arthur', 'Paladino', 4, 14, 'jogador'),
    (90, 'Gwen', 'Clérigo', 1, NULL, 'jogador'),
    (91, 'Lancelot', 'Guerreiro', 28, 1, 'jogador'),
    (92, 'Merlin', 'Mago', 4, 2, 'jogador'),
    (93, 'Morgana', 'Bruxo', 1, 3, 'jogador'),
    (94, 'Percival', 'Paladino', 57, 4, 'jogador'),
    (95, 'Gawain', 'Guerreiro', 1, NULL, 'jogador'),
    (96, 'Tristan', 'Arqueiro', 19, 6, 'jogador'),
    (97, 'Isolde', 'Clérigo', 3, 7, 'jogador'),
    (98, 'Bors', 'Guerreiro', 1, 8, 'jogador'),
    (99, 'Galahad', 'Paladino', 6, 9, 'jogador'),
    (100, 'Mordred', 'Ladino', 1, NULL, 'jogador'),
    (101, 'EduTeste', 'Guerreiro', 1, NULL, 'jogador'),
    (103, 'Dudu', 'Guerreiro', 1, NULL, 'jogador'),
    (104, 'dudu', 'guerreiro', 1, NULL, 'jogador'),
    (105, 'hfghfhg', 'Bardo', 54, 9, 'jogador'),
    (106, 'teste', 'teste', 2, NULL, 'admin'),
    (110, 'sofia', 'Arqueiro', 1, NULL, 'jogador'),
    (112, 'testeAv', 'Mago', 1, NULL, 'jogador');

SELECT pg_catalog.setval('rpg_trabalho_bd.aventureiros_id_seq', 112, TRUE);

-- dungeons: 20 registros
INSERT INTO rpg_trabalho_bd.dungeons (id, nome, dificuldade, xp_recompensa) VALUES
    (1, 'Caverna dos Goblins', 'Fácil', 500),
    (2, 'Torre Esquecida', 'Média', 1500),
    (3, 'Cripta dos Reis Lamentosos', 'Difícil', 5000),
    (4, 'Floresta Sussurrante', 'Fácil', 800),
    (5, 'Abismo de Fogo', 'Extrema', 15000),
    (6, 'Templo da Serpente', 'Média', 2000),
    (7, 'Ruínas de Zann', 'Difícil', 6000),
    (8, 'Labirinto do Minotauro', 'Média', 3000),
    (9, 'Caverna de Cristal', 'Fácil', 1000),
    (10, 'Fosso dos Condenados', 'Difícil', 7500),
    (11, 'Castelo do Vampiro', 'Lendária', 20000),
    (12, 'Minas de Moradin', 'Média', 2500),
    (13, 'Ninho da Aracne', 'Média', 2800),
    (14, 'Covil do Dragão Negro', 'Lendária', 50000),
    (15, 'Ilha das Sombras', 'Difícil', 8000),
    (16, 'Pântano do Desespero', 'Média', 3500),
    (17, 'Montanha Fendida', 'Fácil', 1200),
    (18, 'Fortaleza Vazia', 'Difícil', 7000),
    (19, 'Santuário Submerso', 'Média', 4000),
    (20, 'Passagem do Vento Uivante', 'Fácil', 900);

SELECT pg_catalog.setval('rpg_trabalho_bd.dungeons_id_seq', 20, TRUE);

-- quests: 207 registros
INSERT INTO rpg_trabalho_bd.quests (id, aventureiro_id, dungeon_id, data_quest, sucesso) VALUES
    (1, 1, 1, '2023-01-05', TRUE),
    (2, 2, 2, '2023-01-12', FALSE),
    (3, 3, 3, '2023-01-18', TRUE),
    (4, 4, 4, '2023-01-22', TRUE),
    (5, 5, 5, '2023-02-01', FALSE),
    (6, 6, 6, '2023-02-09', TRUE),
    (7, 7, 7, '2023-02-14', FALSE),
    (8, 8, 8, '2023-02-20', TRUE),
    (9, 9, 9, '2023-02-28', TRUE),
    (10, 10, 10, '2023-03-05', FALSE),
    (11, 11, 11, '2023-03-11', TRUE),
    (12, 12, 12, '2023-03-19', TRUE),
    (13, 13, 13, '2023-03-25', FALSE),
    (14, 14, 14, '2023-04-02', TRUE),
    (15, 15, 15, '2023-04-08', FALSE),
    (16, 16, 16, '2023-04-14', TRUE),
    (17, 17, 17, '2023-04-22', TRUE),
    (18, 18, 18, '2023-04-29', FALSE),
    (19, 19, 19, '2023-05-04', TRUE),
    (20, 20, 20, '2023-05-10', FALSE),
    (21, 21, 1, '2023-05-16', TRUE),
    (22, 22, 2, '2023-05-21', TRUE),
    (23, 23, 3, '2023-05-28', FALSE),
    (24, 24, 4, '2023-06-03', TRUE),
    (25, 25, 5, '2023-06-09', FALSE),
    (26, 26, 6, '2023-06-15', TRUE),
    (27, 27, 7, '2023-06-22', TRUE),
    (28, 28, 8, '2023-06-28', FALSE),
    (29, 29, 9, '2023-07-05', TRUE),
    (30, 30, 10, '2023-07-11', FALSE),
    (31, 31, 11, '2023-07-18', TRUE),
    (32, 32, 12, '2023-07-25', TRUE),
    (33, 33, 13, '2023-08-01', FALSE),
    (34, 34, 14, '2023-08-08', TRUE),
    (35, 35, 15, '2023-08-14', FALSE),
    (36, 36, 16, '2023-08-20', TRUE),
    (37, 37, 17, '2023-08-27', TRUE),
    (38, 38, 18, '2023-09-03', FALSE),
    (39, 39, 19, '2023-09-09', TRUE),
    (40, 40, 20, '2023-09-15', FALSE),
    (41, 41, 1, '2023-09-22', TRUE),
    (42, 42, 2, '2023-09-29', TRUE),
    (43, 43, 3, '2023-10-05', FALSE),
    (44, 44, 4, '2023-10-12', TRUE),
    (45, 45, 5, '2023-10-18', FALSE),
    (46, 46, 6, '2023-10-25', TRUE),
    (47, 47, 7, '2023-11-01', TRUE),
    (48, 48, 8, '2023-11-08', FALSE),
    (49, 49, 9, '2023-11-15', TRUE),
    (50, 50, 10, '2023-11-22', FALSE),
    (51, 51, 11, '2023-11-28', TRUE),
    (52, 52, 12, '2023-12-05', TRUE),
    (53, 53, 13, '2023-12-11', FALSE),
    (54, 54, 14, '2023-12-18', TRUE),
    (55, 55, 15, '2023-12-25', FALSE),
    (56, 56, 16, '2024-01-02', TRUE),
    (57, 57, 17, '2024-01-09', TRUE),
    (58, 58, 18, '2024-01-16', FALSE),
    (59, 59, 19, '2024-01-23', TRUE),
    (60, 60, 20, '2024-01-30', FALSE),
    (61, 61, 1, '2024-02-06', TRUE),
    (62, 62, 2, '2024-02-13', TRUE),
    (63, 63, 3, '2024-02-20', FALSE),
    (64, 64, 4, '2024-02-27', TRUE),
    (65, 65, 5, '2024-03-05', FALSE),
    (66, 66, 6, '2024-03-12', TRUE),
    (67, 67, 7, '2024-03-19', TRUE),
    (68, 68, 8, '2024-03-26', FALSE),
    (69, 69, 9, '2024-04-02', TRUE),
    (70, 70, 10, '2024-04-09', FALSE),
    (71, 71, 11, '2024-04-16', TRUE),
    (72, 72, 12, '2024-04-23', TRUE),
    (73, 73, 13, '2024-04-30', FALSE),
    (74, 74, 14, '2024-05-07', TRUE),
    (75, 75, 15, '2024-05-14', FALSE),
    (76, 76, 16, '2024-05-21', TRUE),
    (77, 77, 17, '2024-05-28', TRUE),
    (78, 78, 18, '2024-06-04', FALSE),
    (79, 79, 19, '2024-06-11', TRUE),
    (80, 80, 20, '2024-06-18', FALSE),
    (81, 81, 1, '2024-06-25', TRUE),
    (82, 82, 2, '2024-07-02', TRUE),
    (83, 83, 3, '2024-07-09', FALSE),
    (84, 84, 4, '2024-07-16', TRUE),
    (85, 85, 5, '2024-07-23', FALSE),
    (86, 86, 6, '2024-07-30', TRUE),
    (87, 87, 7, '2024-08-06', TRUE),
    (88, 88, 8, '2024-08-13', FALSE),
    (89, 89, 9, '2024-08-20', TRUE),
    (90, 90, 10, '2024-08-27', FALSE),
    (91, 91, 11, '2024-09-03', TRUE),
    (92, 92, 12, '2024-09-10', TRUE),
    (93, 93, 13, '2024-09-17', FALSE),
    (94, 94, 14, '2024-09-24', TRUE),
    (95, 95, 15, '2024-10-01', FALSE),
    (96, 96, 16, '2024-10-08', TRUE),
    (97, 97, 17, '2024-10-15', TRUE),
    (98, 98, 18, '2024-10-22', FALSE),
    (99, 99, 19, '2024-10-29', TRUE),
    (100, 100, 20, '2024-11-05', FALSE),
    (101, 1, 20, '2024-11-12', FALSE),
    (102, 2, 19, '2024-11-19', TRUE),
    (103, 3, 18, '2024-11-26', FALSE),
    (104, 4, 17, '2024-12-03', TRUE),
    (105, 5, 16, '2024-12-10', FALSE),
    (106, 6, 15, '2024-12-17', TRUE),
    (107, 7, 14, '2024-12-24', TRUE),
    (108, 8, 13, '2024-12-31', FALSE),
    (109, 9, 12, '2025-01-07', TRUE),
    (110, 10, 11, '2025-01-14', FALSE),
    (111, 11, 10, '2025-01-21', TRUE),
    (112, 12, 9, '2025-01-28', TRUE),
    (113, 13, 8, '2025-02-04', FALSE),
    (114, 14, 7, '2025-02-11', TRUE),
    (115, 15, 6, '2025-02-18', FALSE),
    (116, 16, 5, '2025-02-25', TRUE),
    (117, 17, 4, '2025-03-04', TRUE),
    (118, 18, 3, '2025-03-11', FALSE),
    (119, 19, 2, '2025-03-18', TRUE),
    (120, 20, 1, '2025-03-25', FALSE),
    (121, 21, 20, '2025-04-01', FALSE),
    (122, 22, 19, '2025-04-08', TRUE),
    (123, 23, 18, '2025-04-15', FALSE),
    (124, 24, 17, '2025-04-22', TRUE),
    (125, 25, 16, '2025-04-29', FALSE),
    (126, 26, 15, '2025-05-06', TRUE),
    (127, 27, 14, '2025-05-13', TRUE),
    (128, 28, 13, '2025-05-20', FALSE),
    (129, 29, 12, '2025-05-27', TRUE),
    (130, 30, 11, '2025-06-03', FALSE),
    (131, 31, 10, '2025-06-10', TRUE),
    (132, 32, 9, '2025-06-17', TRUE),
    (133, 33, 8, '2025-06-24', FALSE),
    (134, 34, 7, '2025-07-01', TRUE),
    (135, 35, 6, '2025-07-08', FALSE),
    (136, 36, 5, '2025-07-15', TRUE),
    (137, 37, 4, '2025-07-22', TRUE),
    (138, 38, 3, '2025-07-29', FALSE),
    (139, 39, 2, '2025-08-05', TRUE),
    (140, 40, 1, '2025-08-12', FALSE),
    (141, 41, 20, '2025-08-19', FALSE),
    (142, 42, 19, '2025-08-26', TRUE),
    (143, 43, 18, '2025-09-02', FALSE),
    (144, 44, 17, '2025-09-09', TRUE),
    (145, 45, 16, '2025-09-16', FALSE),
    (146, 46, 15, '2025-09-23', TRUE),
    (147, 47, 14, '2025-09-30', TRUE),
    (148, 48, 13, '2025-10-07', FALSE),
    (149, 49, 12, '2025-10-14', TRUE),
    (150, 50, 11, '2025-10-21', FALSE),
    (151, 51, 10, '2025-10-28', TRUE),
    (152, 52, 9, '2025-11-04', TRUE),
    (153, 53, 8, '2025-11-11', FALSE),
    (154, 54, 7, '2025-11-18', TRUE),
    (155, 55, 6, '2025-11-25', FALSE),
    (156, 56, 5, '2025-12-02', TRUE),
    (157, 57, 4, '2025-12-09', TRUE),
    (158, 58, 3, '2025-12-16', FALSE),
    (159, 59, 2, '2025-12-23', TRUE),
    (160, 60, 1, '2025-12-30', FALSE),
    (161, 61, 20, '2026-01-06', FALSE),
    (162, 62, 19, '2026-01-13', TRUE),
    (163, 63, 18, '2026-01-20', FALSE),
    (164, 64, 17, '2026-01-27', TRUE),
    (165, 65, 16, '2026-02-03', FALSE),
    (166, 66, 15, '2026-02-10', TRUE),
    (167, 67, 14, '2026-02-17', TRUE),
    (168, 68, 13, '2026-02-24', FALSE),
    (169, 69, 12, '2026-03-03', TRUE),
    (170, 70, 11, '2026-03-10', FALSE),
    (171, 71, 10, '2026-03-17', TRUE),
    (172, 72, 9, '2026-03-24', TRUE),
    (173, 73, 8, '2026-03-31', FALSE),
    (174, 74, 7, '2026-04-07', TRUE),
    (175, 75, 6, '2026-04-14', FALSE),
    (176, 76, 5, '2026-04-21', TRUE),
    (177, 77, 4, '2026-04-28', TRUE),
    (178, 78, 3, '2026-05-05', FALSE),
    (179, 79, 2, '2026-05-12', TRUE),
    (180, 80, 1, '2026-05-19', FALSE),
    (181, 81, 20, '2026-05-26', FALSE),
    (182, 82, 19, '2026-06-02', TRUE),
    (183, 83, 18, '2026-06-09', FALSE),
    (184, 84, 17, '2026-06-16', TRUE),
    (185, 85, 16, '2026-06-23', FALSE),
    (186, 86, 15, '2026-06-30', TRUE),
    (187, 87, 14, '2026-07-07', TRUE),
    (188, 88, 13, '2026-07-14', FALSE),
    (189, 89, 12, '2026-07-21', TRUE),
    (190, 90, 11, '2026-07-28', FALSE),
    (191, 91, 10, '2026-08-04', TRUE),
    (192, 92, 9, '2026-08-11', TRUE),
    (193, 93, 8, '2026-08-18', FALSE),
    (194, 94, 7, '2026-08-25', TRUE),
    (195, 95, 6, '2026-09-01', FALSE),
    (196, 96, 5, '2026-09-08', TRUE),
    (197, 97, 4, '2026-09-15', TRUE),
    (198, 98, 3, '2026-09-22', FALSE),
    (199, 99, 2, '2026-09-29', TRUE),
    (200, 100, 1, '2026-10-06', FALSE),
    (203, 1, 2, '2026-10-30', TRUE),
    (204, 1, 2, '2026-10-30', TRUE),
    (10205, 106, 1, '2026-11-03', TRUE),
    (10206, 106, 1, '2026-11-03', TRUE),
    (10209, 105, 8, '2026-11-04', TRUE),
    (10210, 105, 1, '2026-11-04', FALSE),
    (10211, 105, 14, '2026-11-04', TRUE);

SELECT pg_catalog.setval('rpg_trabalho_bd.quests_id_seq', 10212, TRUE);


-- ============================================================================
-- 5. INDICES (INCLUINDO OS QUATRO INDICES DO DOMINIO)
-- ============================================================================

-- cache_expiration_index
CREATE INDEX cache_expiration_index ON rpg_trabalho_bd.cache USING btree (expiration);

-- cache_locks_expiration_index
CREATE INDEX cache_locks_expiration_index ON rpg_trabalho_bd.cache_locks USING btree (expiration);

-- idx_aventureiros_guilda_id
CREATE INDEX idx_aventureiros_guilda_id ON rpg_trabalho_bd.aventureiros USING btree (guilda_id);

-- idx_matheusayres_dificuldade_dungeons
CREATE INDEX idx_matheusayres_dificuldade_dungeons ON rpg_trabalho_bd.dungeons USING btree (dificuldade);

-- idx_matheusayres_nivel_aventureiros
CREATE INDEX idx_matheusayres_nivel_aventureiros ON rpg_trabalho_bd.aventureiros USING btree (nivel);

-- idx_quests_aventureiro_sucesso
CREATE INDEX idx_quests_aventureiro_sucesso ON rpg_trabalho_bd.quests USING btree (aventureiro_id, sucesso);

-- jobs_queue_index
CREATE INDEX jobs_queue_index ON rpg_trabalho_bd.jobs USING btree (queue);

-- personal_access_tokens_expires_at_index
CREATE INDEX personal_access_tokens_expires_at_index ON rpg_trabalho_bd.personal_access_tokens USING btree (expires_at);

-- personal_access_tokens_tokenable_type_tokenable_id_index
CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON rpg_trabalho_bd.personal_access_tokens USING btree (tokenable_type, tokenable_id);

-- sessions_last_activity_index
CREATE INDEX sessions_last_activity_index ON rpg_trabalho_bd.sessions USING btree (last_activity);

-- sessions_user_id_index
CREATE INDEX sessions_user_id_index ON rpg_trabalho_bd.sessions USING btree (user_id);


-- ============================================================================
-- 6. FUNCOES DE XP E NIVEL E FUNCOES DAS TRIGGERS
-- ============================================================================

-- fn_nivel_por_xp(bigint)
CREATE FUNCTION rpg_trabalho_bd.fn_nivel_por_xp(p_xp bigint) RETURNS integer
    LANGUAGE sql IMMUTABLE STRICT PARALLEL SAFE
    AS $$
    select least(1 + (greatest(p_xp, 0) / 1000)::integer, 100); --greatest impede que o xp seja negativo
$$;

-- fn_total_xp(integer)
CREATE FUNCTION rpg_trabalho_bd.fn_total_xp(p_aventureiro_id integer) RETURNS bigint
    LANGUAGE sql STABLE
    AS $$
    select coalesce(sum(d.xp_recompensa), 0)::bigint
    from quests q
    join dungeons d on d.id = q.dungeon_id
    where q.aventureiro_id = p_aventureiro_id
      and q.sucesso = true;
$$;

-- fn_atualizar_nivel_aventureiro(integer)
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

-- fn_trg_proteger_recompensa_dungeon()
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

-- fn_trg_validar_nivel_minimo_guilda()
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


-- ============================================================================
-- 7. PROCEDURES DE REGISTRO DE QUEST E VINCULO DE GUILDA
-- ============================================================================

-- pr_registrar_quest(integer, integer, date, boolean)
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

-- pr_vincular_aventureiro_guilda(integer, integer)
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


-- ============================================================================
-- 8. TRIGGERS DE NIVEL MINIMO E PROTECAO DA RECOMPENSA
-- ============================================================================

-- dungeons trg_dungeons_proteger_recompensa
CREATE TRIGGER trg_dungeons_proteger_recompensa BEFORE UPDATE OF xp_recompensa ON rpg_trabalho_bd.dungeons FOR EACH ROW EXECUTE FUNCTION rpg_trabalho_bd.fn_trg_proteger_recompensa_dungeon();

-- guildas trg_guildas_validar_nivel_minimo
CREATE TRIGGER trg_guildas_validar_nivel_minimo BEFORE INSERT OR UPDATE OF nivel_minimo ON rpg_trabalho_bd.guildas FOR EACH ROW EXECUTE FUNCTION rpg_trabalho_bd.fn_trg_validar_nivel_minimo_guilda();


-- ============================================================================
-- 9. VIEWS E RANKING MATERIALIZADO
-- ============================================================================

-- vw_matheusayres_aventureiros_guildas
CREATE VIEW rpg_trabalho_bd.vw_matheusayres_aventureiros_guildas AS
 SELECT a.nickname,
    a.classe,
    a.nivel,
    COALESCE(g.nome, 'Sem Guilda'::character varying) AS nome_guilda
   FROM (rpg_trabalho_bd.aventureiros a
     LEFT JOIN rpg_trabalho_bd.guildas g ON ((a.guilda_id = g.id)));

-- vw_matheusayres_aventureiros_inativos
CREATE VIEW rpg_trabalho_bd.vw_matheusayres_aventureiros_inativos AS
 SELECT nickname,
    classe
   FROM rpg_trabalho_bd.aventureiros
  WHERE (NOT (id IN ( SELECT quests.aventureiro_id
           FROM rpg_trabalho_bd.quests)));

-- vw_matheusayres_dungeons_alto_xp
CREATE VIEW rpg_trabalho_bd.vw_matheusayres_dungeons_alto_xp AS
 SELECT nome,
    dificuldade,
    xp_recompensa
   FROM rpg_trabalho_bd.dungeons
  WHERE ((xp_recompensa)::numeric > ( SELECT avg(dungeons_1.xp_recompensa) AS avg
           FROM rpg_trabalho_bd.dungeons dungeons_1));

-- vw_matheusayres_historico_sucesso
CREATE VIEW rpg_trabalho_bd.vw_matheusayres_historico_sucesso AS
 SELECT q.data_quest,
    a.nickname,
    d.nome AS dungeon,
    d.xp_recompensa
   FROM ((rpg_trabalho_bd.quests q
     JOIN rpg_trabalho_bd.aventureiros a ON ((q.aventureiro_id = a.id)))
     JOIN rpg_trabalho_bd.dungeons d ON ((q.dungeon_id = d.id)))
  WHERE (q.sucesso = true);

-- vw_matheusayres_ranking_xp
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

REFRESH MATERIALIZED VIEW rpg_trabalho_bd.vw_matheusayres_ranking_xp;

COMMIT;

-- ============================================================================
-- 10. CONFERENCIA DA IMPORTACAO (SOMENTE LEITURA)
-- ============================================================================

SELECT
    (SELECT count(*) FROM rpg_trabalho_bd.guildas) AS guildas,
    (SELECT count(*) FROM rpg_trabalho_bd.aventureiros) AS aventureiros,
    (SELECT count(*) FROM rpg_trabalho_bd.dungeons) AS dungeons,
    (SELECT count(*) FROM rpg_trabalho_bd.quests) AS quests;

SELECT * FROM rpg_trabalho_bd.vw_matheusayres_ranking_xp ORDER BY xp_total_ganho DESC, nickname;

-- ============================================================================
-- 11. EXEMPLOS E COMANDOS DE BACKUP (DOCUMENTACAO; NAO EXECUTADOS)
-- ============================================================================

-- Os exemplos a seguir foram preservados como comentarios para nao alterar
-- os dados importados nem interromper a criacao com erros esperados de triggers.

-- Origem: SQL/log_sql/log.sql
-- -- SQLS GERIAS UTILIZADOS NO TRABALHO
--
-- --utilizado para atualizar o nível em massa
-- set search_path to rpg_trabalho_bd;
--
-- DO $$
-- DECLARE
--     v_aventureiro RECORD;
-- BEGIN
--     FOR v_aventureiro IN
--         SELECT id
--         FROM aventureiros
--     LOOP
--         PERFORM fn_atualizar_nivel_aventureiro(
--             v_aventureiro.id
--         );
--     END LOOP;
-- END $$;
--
-- -- testando a procedure registrar_quest
-- call pr_registrar_quest(
--     1,
--     2,
--     current_date,
--     true,
--     null,
--     null
-- );
--
-- select *
-- from rpg_trabalho_bd.quests
-- where aventureiro_id = 1
--   and dungeon_id = 2
--   and data_quest = current_date
--   and sucesso = true;
--
-- -- testando a procedure vincular_aventureiro_guilda
-- call pr_vincular_aventureiro_guilda(5, 4);
--
-- --testando a trigger fn_trg_validar_nivel_minimo_guilda
-- update guildas
-- set nivel_minimo = 6
-- where id = 1;
-- --lançou exceção
--
-- --testando a trigger fn_trg_proteger_recompensa_dungeon
-- update dungeons
-- set xp_recompensa = xp_recompensa + 100
-- where id = 1;
-- --lançou exceção
--
-- --testando o index idx_quests_aventureiro_sucesso
-- --inserindo 10.000 quests com ajuda da ia e dando rollback dps p não atualizar o nivel dos aventureiros em massa
-- set search_path to rpg_trabalho_bd;
--
-- begin;
--
-- with aventureiros_ids as materialized (
--     select array_agg(id) as ids
--     from rpg_trabalho_bd.aventureiros
-- ),
-- dungeons_ids as materialized (
--     select array_agg(id) as ids
--     from rpg_trabalho_bd.dungeons
-- )
-- insert into rpg_trabalho_bd.quests (
--     aventureiro_id,
--     dungeon_id,
--     data_quest,
--     sucesso
-- )
-- select
--     a.ids[1 + floor(random() * cardinality(a.ids))::integer],
--     d.ids[1 + floor(random() * cardinality(d.ids))::integer],
--     current_date - floor(random() * 365)::integer,
--     random() < 0.7
-- from generate_series(1, 10000)
-- cross join aventureiros_ids a
-- cross join dungeons_ids d
-- where cardinality(a.ids) > 0
--   and cardinality(d.ids) > 0;
--
-- analyze rpg_trabalho_bd.quests;
--
-- explain (analyze, buffers)
-- select aventureiro_id, sucesso
-- from rpg_trabalho_bd.quests
-- where aventureiro_id = 1
--   and sucesso = true;
--
-- rollback;
--

-- Origem: SQL/backups
-- cd PASTA_DO_SEU_BACKUP
--
-- -- Executando o backup via cmd
-- pg_dump -Fc -v `
--     -h "HOST_DO_SEU_BANCO" `
--     -p 5432 `
--     -U "USUARIO_DO_SEU_BANCO" `
--     -d "rpg_trabalho_bd" `
--     -W `
--     -f ".\backup_rpg_2026-11-04.dump"
--
-- -- Conferindo
-- Get-Item .\backup_rpg_2026-11-04.dump |
--     Select-Object Name, Length, LastWriteTime
--
-- -- Criando um banco novo
-- create database rpg_backup_teste_20261104;
--
-- -- Restaurando
-- pg_restore -v `
--     --exit-on-error `
--     --no-owner `
--     --no-acl `
--     -h "HOST_DO_SEU_BANCO" `
--     -p 5432 `
--     -U "USUARIO_DO_SEU_BANCO" `
--     -d "rpg_backup_teste_20261104" `
--     -W `
--     ".\backup_rpg_2026-11-04.dump"
--
-- -- Conferindo (executado em ambas bases de dados)
--
-- select
--     current_database() as banco,
--     (select count(*) from rpg_trabalho_bd.aventureiros) as aventureiros,
--     (select count(*) from rpg_trabalho_bd.guildas) as guildas,
--     (select count(*) from rpg_trabalho_bd.dungeons) as dungeons,
--     (select count(*) from rpg_trabalho_bd.quests) as quests;
--
