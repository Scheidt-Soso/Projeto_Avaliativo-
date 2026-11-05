<div align="center">

```text
╔══════════════════════════════════════════════════════════╗
║                     COROA DE CINZAS                      ║
║          quartel de guildas · banco de dados II          ║
╚══════════════════════════════════════════════════════════╝
```

**Guildas, aventureiros, dungeons e quests em um só quartel.**

![PHP](https://img.shields.io/badge/PHP-8.2%2B-090909?style=flat-square&labelColor=f2f2ec)
![Laravel](https://img.shields.io/badge/Laravel-12-090909?style=flat-square&labelColor=f2f2ec)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16%2B-090909?style=flat-square&labelColor=f2f2ec)
![Vite](https://img.shields.io/badge/Vite-7-090909?style=flat-square&labelColor=f2f2ec)
![Testes](https://img.shields.io/badge/testes-83%20%2B%2011-090909?style=flat-square&labelColor=f2f2ec)

</div>

---

## ▸ Visão geral

**Coroa de Cinzas** é o projeto bimestral de **Banco de Dados II e Back-end**. A aplicação é um gerenciador de guildas construído sobre o banco original `rpg_trabalho_bd`, com quatro entidades centrais:

- `guildas`
- `aventureiros`
- `dungeons`
- `quests`

O sistema tem interface web em Blade, API JSON com Sanctum e regras de progressão executadas diretamente no PostgreSQL por procedures, functions e triggers.

A identidade visual segue o quartel: **preto e branco, tipografia pixelada (VT323), bordas retas, textura de terminal e cenários em pixel art**.

---

## ▸ Funcionalidades

### Interface web

- Login e cadastro de aventureiros por `nickname` e `senha`.
- Painel do quartel com membros, expedições, taxa de sucesso, XP e histórico.
- Troca de guilda e entrada em guildas compatíveis com o nível.
- Cadastros completos de guildas, aventureiros, dungeons e quests.
- Busca, filtros, paginação, ficha, criação, edição e exclusão.
- Perfil do aventureiro com XP, histórico e vínculo de guilda.
- Relatórios com as cinco views originais do banco.
- Protótipo de seleção de personagem em `/aventureiros`.
- Proteção CSRF, sessão renovada no login e limite de tentativas.

### API

- Autenticação por token ou sessão stateful com Sanctum.
- Catálogo público de dungeons.
- Área do jogador em `/api/jogador/*`.
- Área administrativa em `/api/admin/*`.
- Sem rotas genéricas `/api/{recurso}`.
- Papel (`admin` ou `jogador`) sempre lido do banco.

### Banco de dados

- `pr_registrar_quest`: registra a quest e devolve XP e nível recalculados.
- `pr_vincular_aventureiro_guilda`: valida o nível mínimo antes do vínculo.
- `fn_total_xp`, `fn_nivel_por_xp`, `fn_atualizar_nivel_aventureiro`.
- Triggers que protegem recompensas de dungeons e o nível mínimo das guildas.
- Views de ranking, vínculos, aventureiros inativos, dungeons de alto XP e histórico de sucesso.
- Ranking materializado atualizado após mudanças relevantes.

---

## ▸ Stack

| Camada | Tecnologia |
| --- | --- |
| Backend | Laravel 12 · PHP 8.2+ |
| Banco | PostgreSQL (Neon ou local) · schema `rpg_trabalho_bd` |
| Autenticação | Sessão web + Laravel Sanctum |
| Frontend | Blade · Vite 7 · Tailwind CSS 4 |
| Testes | PHPUnit 11 · SQLite em memória · PostgreSQL real |
| Estilo | Preto e branco · VT323 · pixel art · terminal |

---

## ▸ Pré-requisitos

- **PHP 8.2 ou superior** com as extensões:
  - `pdo_pgsql`
  - `mbstring`
  - `openssl`
  - `fileinfo`
  - `curl`
  - `zip`
- **Composer**
- **Node.js 20+** e **npm**
- **PostgreSQL 16+** local **ou** credenciais do banco Neon do grupo
- **Git**

Para conferir as extensões:

```bash
php -m | grep -E 'pdo_pgsql|mbstring|openssl|fileinfo|curl|zip'
```

---

## ▸ Como rodar

### 1. Clonar o repositório

```bash
git clone <URL_DO_REPOSITORIO>
cd Back_Banco
```

### 2. Instalar dependências

```bash
composer install
npm install
```

### 3. Criar o `.env`

Linux/macOS:

```bash
cp .env.example .env
php artisan key:generate
```

Windows (PowerShell):

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

Configure o banco no `.env`:

```env
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=rpg_trabalho_bd
DB_USERNAME=postgres
DB_PASSWORD=sua_senha
DB_SSLMODE=prefer
DB_SEARCH_PATH=rpg_trabalho_bd,public

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

> **Neon:** use o host, banco, usuário, senha e `DB_SSLMODE=require` fornecidos pelo grupo.
>
> **PostgreSQL local:** normalmente use `DB_SSLMODE=disable` ou `prefer`.

### 4. Preparar o banco

O projeto **não usa migrations** para criar o schema original. O banco precisa já existir com as tabelas, procedures, functions, triggers e views.

#### Opção A — banco compartilhado (Neon)

Solicite as credenciais ao grupo e preencha o `.env`. Não rode migrations nem restaure o snapshot nesse banco.

#### Opção B — PostgreSQL local

Crie o banco:

```bash
createdb -h 127.0.0.1 -U postgres rpg_trabalho_bd
```

Carregue o snapshot de estrutura:

```bash
# PostgreSQL 18
psql -h 127.0.0.1 -U postgres -d rpg_trabalho_bd \
  -f database/schema/rpg_trabalho_bd.sql
```

Em PostgreSQL 16/17, remova as linhas que o cliente antigo não entende:

```bash
sed -e '/^\\restrict/d' -e '/^\\unrestrict/d' -e '/^SET transaction_timeout/d' \
  database/schema/rpg_trabalho_bd.sql \
  | psql -h 127.0.0.1 -U postgres -d rpg_trabalho_bd
```

O snapshot contém **apenas a estrutura**, sem dados. Para o quartel abrir, insira um registro mínimo:

```sql
INSERT INTO rpg_trabalho_bd.guildas (nome, nivel_minimo, data_fundacao)
VALUES ('Quartel de Cinzas', 1, CURRENT_DATE);

INSERT INTO rpg_trabalho_bd.dungeons (nome, dificuldade, xp_recompensa)
VALUES ('Cripta Inicial', 'Fácil', 500);

REFRESH MATERIALIZED VIEW rpg_trabalho_bd.vw_matheusayres_ranking_xp;
```

> O dump com os dados reais não está versionado. A evidência de backup/restauração está em `docs/evidencias/restauracao.json`.

### 5. Compilar os assets e subir o servidor

```bash
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Abra **http://127.0.0.1:8000**.

Para desenvolver com atualização automática:

```bash
# terminal 1
php artisan serve --host=127.0.0.1 --port=8000

# terminal 2
npm run dev
```

---

## ▸ Como entrar e testar

### Criar uma conta de jogador

1. Acesse `http://127.0.0.1:8000/register`.
2. Escolha um `nickname`, uma classe e uma senha com pelo menos 6 caracteres.
3. Confirme o cadastro.
4. Entre em `/login` com o mesmo `nickname` e senha.

O cadastro cria automaticamente:

- `nivel = 1`
- `role = jogador`
- `guilda_id = null`
- senha protegida por hash

Campos extras como `role`, `nivel` e `guilda_id` enviados pelo formulário são ignorados.

### Transformar a conta em administradora

Depois de criar a conta, atualize o papel direto no banco:

```sql
UPDATE rpg_trabalho_bd.aventureiros
SET role = 'admin'
WHERE nickname = 'SEU_NICKNAME';
```

Saia e entre novamente. A senha continua a mesma.

> Contas antigas sem senha cadastrada não conseguem entrar. O sistema não define senhas padrão nem altera contas existentes.

### O que cada papel pode fazer

| Ação | Jogador | Administrador |
| --- | --- | --- |
| Ver o quartel | sim | sim |
| Consultar atlas e histórico | sim | sim |
| Entrar em guilda compatível | sim | sim |
| Ver o próprio perfil | sim | sim |
| Ver relatórios | sim | sim |
| Criar/editar/excluir cadastros | não | sim |
| Registrar resultado de quest | não | sim |
| Atualizar ranking manualmente | não | sim |
| Usar `/api/admin/*` | não | sim |

### Roteiro rápido de teste

**Jogador**

1. Entre em `/login`.
2. Abra o quartel em `/`.
3. Use **Consultar atlas** para ver dungeons e histórico.
4. Escolha uma dungeon para o seu aventureiro.
5. Abra `/perfil` e vincule sua conta a uma guilda compatível.
6. Confirme que você **não** consegue registrar sucesso ou falha de quest.

**Administrador**

1. Promova a conta com o `UPDATE` acima e entre novamente.
2. Abra `/cadastros/guildas`, `/cadastros/aventureiros`, `/cadastros/dungeons` e `/cadastros/quests`.
3. Crie, edite e exclua um registro de teste.
4. Registre uma quest com sucesso e outra com falha.
5. Confira o XP e o nível recalculados no perfil e nos relatórios.
6. Abra `/relatorios` e atualize o ranking manualmente.

### Teste rápido da API

```bash
# catálogo público
curl -s http://127.0.0.1:8000/api/dungeons \
  -H 'Accept: application/json'

# login e obtenção do token
curl -s -X POST http://127.0.0.1:8000/api/login \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"nickname":"SEU_NICKNAME","senha":"SUA_SENHA"}'

# usuário autenticado
curl -s http://127.0.0.1:8000/api/user \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer SEU_TOKEN'
```

---

## ▸ Testes automatizados

### Suíte principal (rápida, sem tocar no Neon)

Requer `pdo_sqlite`. Usa SQLite em memória com o schema `rpg_trabalho_bd` anexado.

```bash
php artisan test
```

Um arquivo por vez:

```bash
php artisan test tests/Feature/AuthenticationTest.php
php artisan test tests/Feature/ApiCrudTest.php
php artisan test tests/Feature/ApiAuthorizationTest.php
php artisan test tests/Feature/WebCrudTest.php
php artisan test tests/Feature/PerfilTest.php
php artisan test tests/Feature/GuildDashboardTest.php
php artisan test tests/Feature/ReportsTest.php
php artisan test tests/Feature/ProcedureEndpointsTest.php
php artisan test tests/Feature/AventureiroSelectionTest.php
```

### Suíte PostgreSQL real

Executa procedures, triggers, cascatas e rollback em um PostgreSQL local. O host é fixo em `127.0.0.1`.

Com Docker:

```bash
docker run -d --name coroa_pg_test \
  -e POSTGRES_USER=codex_qa \
  -e POSTGRES_PASSWORD=coroa_test_pw \
  -e POSTGRES_DB=coroa_qa \
  -p 55432:5432 \
  postgres:16

sed -e '/^\\restrict/d' -e '/^\\unrestrict/d' -e '/^SET transaction_timeout/d' \
  database/schema/rpg_trabalho_bd.sql \
  | docker exec -i -e PGPASSWORD=coroa_test_pw coroa_pg_test \
      psql -U codex_qa -d coroa_qa

docker exec -e PGPASSWORD=coroa_test_pw coroa_pg_test \
  psql -U codex_qa -d coroa_qa \
  -c 'REFRESH MATERIALIZED VIEW rpg_trabalho_bd.vw_matheusayres_ranking_xp;'
```

Rodar:

```bash
COROA_TEST_LOCAL=1 \
COROA_TEST_PASSWORD=coroa_test_pw \
COROA_TEST_PORT=55432 \
COROA_TEST_DATABASE=coroa_qa \
COROA_TEST_USER=codex_qa \
php vendor/phpunit/phpunit/phpunit -c phpunit.postgresql.xml
```

> Se a view materializada estiver vazia, rode o `REFRESH MATERIALIZED VIEW` acima antes da suíte.

### Cobertura atual

- Autenticação, cadastro, limite de tentativas e sigilo da senha.
- Renovação e invalidação de sessão.
- Permissões de jogador e administrador.
- CRUD web e CRUD completo da API administrativa.
- Busca, filtros, paginação e proteção contra envio duplicado.
- Escolha explícita de dungeon e histórico por guilda/aventureiro.
- Procedures, triggers, cascatas, rollback e ranking no PostgreSQL real.

### Estilo de código

```bash
./vendor/bin/pint --test   # apenas verifica
./vendor/bin/pint          # corrige
```

---

## ▸ Comandos úteis

| Comando | Função |
| --- | --- |
| `php artisan serve --host=127.0.0.1 --port=8000` | Sobe o servidor local |
| `npm run dev` | Assets com recarga automática |
| `npm run build` | Build de produção |
| `php artisan test` | Suíte principal |
| `php artisan route:list` | Lista as rotas |
| `php artisan config:clear` | Limpa cache de configuração |
| `php artisan view:clear` | Limpa views compiladas |
| `./vendor/bin/pint --test` | Verifica estilo |

Conferir rotas duplicadas:

```bash
php artisan route:list --json > /tmp/routes.json
php -r '$r=json_decode(file_get_contents("/tmp/routes.json"),true); $n=array_filter(array_column($r,"name")); $c=array_count_values($n); $d=array_filter($c,fn($v)=>$v>1); echo "rotas: ".count($r)."\n"; echo "duplicadas: ".count($d)."\n";'
```

---

## ▸ Estrutura do projeto

```text
Back_Banco/
├── app/
│   ├── Http/Controllers/Api/       # API jogador e admin
│   ├── Http/Controllers/Web/       # Telas Blade
│   ├── Http/Middleware/            # role:jogador / role:admin
│   ├── Http/Requests/              # Validação de login e cadastro
│   ├── Models/                     # Aventureiro, Guilda, Dungeon, Quest
│   ├── Services/                   # Regras de negócio e procedures
│   └── Support/                    # Regras, exibição e erros do banco
├── config/
│   ├── appearance.php              # Aparência por classe
│   └── guild-bases.php             # Identidade visual por guilda
├── database/
│   ├── schema/rpg_trabalho_bd.sql  # Snapshot de estrutura
│   └── migrations/                 # Apenas senha/role (não usar no banco original)
├── docs/                           # Status do banco, evidências e assets
├── resources/
│   ├── css/                        # app, auth, guild e catalog
│   ├── js/                         # cena, atlas e elenco
│   └── views/                      # Blade
├── routes/
│   ├── api.php                     # /api/*
│   └── web.php                     # Telas e sessão
├── SQL/                            # Scripts originais de procedures/triggers
├── tests/
│   ├── Feature/                    # Testes HTTP/unitários
│   ├── Integration/                # PostgreSQL real
│   └── Support/                    # FakeProcedures para SQLite
├── phpunit.xml                     # Suíte SQLite
└── phpunit.postgresql.xml          # Suíte PostgreSQL
```

---

## ▸ Rotas principais

### Web

| Método | Rota | Acesso | Função |
| --- | --- | --- | --- |
| GET/POST | `/login` | visitante | Entrar com nickname e senha |
| GET/POST | `/register` | visitante | Cadastrar aventureiro |
| POST | `/logout` | autenticado | Encerrar sessão |
| GET | `/` | autenticado | Painel do quartel |
| POST | `/guildas/{guilda}/expedicao/selecionar` | autenticado | Escolher dungeon para um membro |
| GET | `/perfil` | autenticado | Perfil, XP e histórico |
| POST | `/perfil/guilda` | autenticado | Entrar em guilda compatível |
| GET | `/relatorios` | autenticado | Cinco relatórios do banco |
| POST | `/relatorios/atualizar` | admin | Atualizar ranking |
| GET | `/cadastros/{recurso}` | autenticado | Listagem dos cadastros |
| POST/PUT/DELETE | `/cadastros/{recurso}` | admin | Criar, editar e excluir |
| GET | `/aventureiros` | autenticado | Protótipo de seleção de personagem |

### API

| Método | Rota | Acesso | Função |
| --- | --- | --- | --- |
| POST | `/api/login` | público | Login e emissão de token |
| POST | `/api/register` | público | Cadastro de jogador |
| GET | `/api/dungeons` | público | Catálogo de dungeons |
| GET | `/api/dungeons/{id}` | público | Detalhe da dungeon |
| GET | `/api/user` | autenticado | Conta sem a senha |
| POST | `/api/logout` | autenticado | Revogar token ou sessão |
| GET | `/api/jogador/*` | jogador | Consultar cadastros |
| POST | `/api/jogador/aventureiros/{id}/guilda` | jogador | Vincular o próprio aventureiro |
| GET/POST/PUT/PATCH/DELETE | `/api/admin/*` | admin | CRUD completo |
| POST | `/api/admin/quests/registrar` | admin | Registrar resultado via procedure |
| POST | `/api/admin/aventureiros/{id}/guilda` | admin | Vincular qualquer aventureiro |

---

## ▸ Regras de negócio e banco

- O login usa `nickname` e `senha` da tabela `rpg_trabalho_bd.aventureiros`. Não existe cadastro em `users`.
- Senhas são verificadas por hash e nunca aparecem em JSON.
- O cadastro público cria apenas `role = jogador`, nível 1 e sem guilda.
- Cinco falhas de login por nickname/IP bloqueiam novas tentativas por um minuto.
- Somente administradores registram, corrigem ou excluem resultados de quests.
- Jogadores não escolhem sucesso/falha nem alteram XP/nível, mesmo enviando campos forjados.
- O registro de quests usa `pr_registrar_quest`.
- Correções e exclusões recalculam os níveis com as functions do banco.
- Vínculos de guilda usam `pr_vincular_aventureiro_guilda`.
- Triggers preservam o nível mínimo e a recompensa de dungeons já exploradas.
- As rotinas rodam em transações com `SET LOCAL search_path`.
- O ranking materializado é atualizado após mudanças relevantes.
- Não existe sorteio nem probabilidade de vitória no banco: o resultado é informado pelo administrador.
- A seleção de destino não registra quest nem altera nível.

---

## ▸ Identidade visual

- Paleta monocromática: preto `#090909`, papel `#f2f2ec`, tons de cinza.
- Tipografia pixelada **VT323**, servida localmente em `public/fonts/`.
- Cenários em canvas com parallax, patrulheiros, bandeiras, tochas, portal e partículas.
- Biomas, arquiteturas e estandartes definidos em `config/guild-bases.php`.
- Aparência por classe definida em `config/appearance.php`.
- Respeito a `prefers-reduced-motion` e pausa manual das animações.

Para usar um retrato próprio:

```php
'characters' => [
    42 => ['portrait' => 'images/portraits/pedro.webp'],
],
```

Coloque o arquivo em `public/images/portraits/`. A imagem substitui apenas o retrato; classe, nível e guilda continuam vindo do banco.

---

## ▸ Equipe e features

| Integrante | Principais entregas |
| --- | --- |
| **Dudu** | Models iniciais, schema e relacionamentos; migration de `senha`/`role`; login, registro e logout; `Authenticatable`; middleware de role; vínculo de guilda via API |
| **Matheus Ayres** | Functions de XP e nível; procedures de quest e vínculo; triggers de recompensa e nível mínimo; índices; log SQL e backup; conexão com o Neon |
| **Pedro Forbeck** | Layout e home; models, services, resources e controllers; seleção de aventureiro; quartel de guildas em canvas; CRUDs Blade; perfil e relatórios; testes PostgreSQL; permissões e histórico de dungeons |
| **Sofia Scheidt Alves** | API com Sanctum; dashboards e proteção admin por role; exposição das procedures via API; unificação `/api/jogador` e `/api/admin`; testes de CRUD/autorização; FormRequests de login/cadastro; integração das branches |

---

## ▸ Solução de problemas

**`could not find driver` ou erro de `pdo_pgsql`**

Instale a extensão do PostgreSQL para o PHP e reinicie o servidor.

```bash
php -m | grep pdo_pgsql
```

**`No application encryption key has been specified`**

```bash
php artisan key:generate
```

**`Vite manifest not found`**

```bash
npm install
npm run build
```

**Erro de SSL ao conectar no PostgreSQL local**

Use `DB_SSLMODE=disable` ou `DB_SSLMODE=prefer`. No Neon, mantenha `require`.

**View materializada não populada**

```sql
REFRESH MATERIALIZED VIEW rpg_trabalho_bd.vw_matheusayres_ranking_xp;
```

**Login falha para uma conta antiga**

Aventureiros sem `senha` cadastrada não podem entrar. Crie uma conta nova em `/register` ou defina uma senha com hash para a conta existente.

**`419 Page Expired`**

A sessão expirou ou o token CSRF não foi enviado. Recarregue a página e tente novamente.

**API devolve 302/HTML em vez de JSON**

Envie o cabeçalho `Accept: application/json`.

**Permissão negada em `storage/` ou `bootstrap/cache/`**

```bash
chmod -R ug+rwX storage bootstrap/cache
```

---

## ▸ Documentação adicional

- [`docs/database-status.md`](docs/database-status.md) — estrutura, rotinas e estado do banco.
- [`docs/verificacao-final.md`](docs/verificacao-final.md) — conferência de resultados e permissões.
- [`docs/visual-assets.md`](docs/visual-assets.md) — origem dos assets visuais.
- [`docs/evidencias/restauracao.json`](docs/evidencias/restauracao.json) — evidência de backup/restauração.
- `SQL/` — scripts originais de functions, procedures, triggers e índices.

---

<div align="center">

```text
  ┌──────────────────────────────────────────────┐
  │  o quartel está aberto. escolha sua dungeon. │
  └──────────────────────────────────────────────┘
```

</div>
