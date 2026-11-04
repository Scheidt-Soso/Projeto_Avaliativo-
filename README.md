# Coroa de Cinzas

Aplicação do projeto bimestral de Banco de Dados II e Back-end, com Laravel 12, Blade, PostgreSQL no Neon e Vite/Tailwind. O produto agora é um gerenciador de guildas, centrado no banco original de guildas, aventureiros, dungeons e quests.

## Autenticação

O login usa `nickname` e `senha` da própria tabela `rpg_trabalho_bd.aventureiros`. A model é o usuário da sessão Laravel; não é preciso criar uma conta em `users`, adicionar e-mail, token de permanência ou executar migrations. Senhas são verificadas por hash, nunca devolvidas em JSON ou mantidas no formulário após erro. Entrar renova a sessão; sair invalida a sessão e as seleções anteriores.

O cadastro integrado da `feature/dudu` cria um aventureiro com senha protegida por hash, nível 1, papel `jogador` e sem guilda. Campos extras enviados pelo cliente não permitem escolher nível, guilda ou papel administrativo. As classes sugeridas vêm dos aventureiros cadastrados. Os aventureiros antigos sem senha não podem entrar; esta implementação não atribui senhas padrão nem redefine contas existentes.

| Método | Rota | Função |
| --- | --- | --- |
| GET / POST | `/login` | Formulário e entrada por nickname e senha. Cinco falhas por nickname/IP bloqueiam novas tentativas por um minuto. |
| GET / POST | `/register` | Formulário e cadastro de aventureiro. |
| POST | `/logout` | Encerra a sessão, com proteção CSRF. |

O painel e os cadastros exigem login. A API aceita sessão do site por Sanctum ou token emitido em `/api/login`; `/api/user` devolve a conta sem a senha. O catálogo `GET /api/dungeons` é público. Jogadores usam `/api/jogador/*` para consultar cadastros, vincular o próprio aventureiro a uma guilda e registrar as próprias quests. Administradores usam `/api/admin/*` para criar, editar e excluir os quatro tipos de registro. Não existem rotas genéricas `/api/{recurso}`. O papel vem do banco (`admin` ou `jogador`); o cadastro público cria apenas jogadores. Para outro domínio/porta, configure `SANCTUM_STATEFUL_DOMAINS` e envie os cookies e o token CSRF.

## Cadastros, perfil e relatórios

As páginas `/cadastros/guildas`, `/cadastros/aventureiros`, `/cadastros/dungeons` e `/cadastros/quests` possuem listagem, busca, filtros, paginação, ficha, criação, edição e exclusão. Alterações exigem administrador. A exclusão mostra os efeitos sobre registros relacionados e exige digitar o nome do registro. Formulários de criação usam um identificador para evitar duplicação por reenvio. Uma conta administrativa não pode excluir ou rebaixar a si mesma durante a sessão.

Em `/perfil`, cada pessoa vê seu aventureiro, XP e histórico; pode entrar em uma guilda compatível com o nível e registrar o resultado real de uma expedição. A dungeon é escolhida explicitamente. O servidor usa o ID autenticado, mesmo que outro ID seja enviado pelo cliente.

O registro de quests usa `pr_registrar_quest`. Correções e exclusões recalculam os níveis envolvidos com as funções do banco. Vínculos de guilda usam `pr_vincular_aventureiro_guilda`; as triggers preservam os requisitos de nível e a recompensa de dungeons já exploradas. As rotinas rodam em transações com `SET LOCAL search_path`, e o ranking materializado é atualizado após mudanças relevantes.

Em `/relatorios`, as cinco views originais fornecem ranking de XP, vínculos de guildas, aventureiros sem quests, dungeons com XP acima da média e histórico de sucessos. Os relatórios têm busca e paginação; administradores podem atualizar o ranking manualmente. Perfil e relatórios estão no menu da conta.

A tipografia foi ampliada: campos usam 16px, tabelas usam 15px e os textos auxiliares ganharam tamanho e contraste. A aparência monocromática e as animações permanecem.

## Feature 2: quartel de guildas em preto e branco

A home agora é o painel da guilda: troca de guilda, membros com fichas, total de expedições, taxa de sucesso, XP conquistado e últimas quests. Todos os dados vêm das tabelas originais. O XP do painel soma as recompensas atuais das dungeons das quests bem-sucedidas dos membros atuais da guilda.

O quartel é desenhado e animado em canvas, com parallax, patrulheiros, bandeiras, tochas, portal e partículas. A tipografia VT323 é servida localmente, com sua licença OFL. Há pausa manual de animações e respeito a `prefers-reduced-motion`.

Cada guilda original possui nome de quartel, região e mensagem próprios, definidos em `config/guild-bases.php`. A cena combina oito biomas, quatro arquiteturas, estandartes e marcos locais: neve, chuva, brasa, folhas ou poeira astral. O cenário é estável para a mesma guilda; bases vazias não recebem patrulheiros fictícios. Guildas novas recebem um perfil padrão. A identidade visual não altera o banco.

O elenco permite buscar por nome, combinar a classe e ordenar por nível, nome ou número de expedições. Os filtros atuam nos membros já carregados e não alteram os totais do painel nem a lista de envio para expedições. Sem JavaScript, a listagem completa continua disponível.

O botão **Consultar atlas** mostra as dungeons do banco com dificuldade, recompensa e vitórias/tentativas da guilda atual. É possível buscar, filtrar dificuldade, ordenar por XP e destacar destinos ainda não explorados. Esses números descrevem o histórico, não a chance de vitória futura.

A preparação exige escolher explicitamente um aventureiro permitido e uma dungeon cadastrada, pelo formulário ou pelo atlas. O servidor valida os IDs e guarda a seleção na sessão; ao abrir o painel, consulta novamente os dados atuais. **Escolher um destino não registra uma quest nem muda o nível.** O botão de registrar resultado abre o formulário correspondente; a confirmação grava a quest e aplica a progressão original. O banco não define sorteio ou probabilidade de vitória.

| Método | Rota | Função |
| --- | --- | --- |
| GET | `/?guilda=1` | Painel da guilda e histórico real. Sem parâmetro, abre a primeira guilda. |
| POST | `/guildas/{guilda}/expedicao/selecionar` | Salva a dungeon escolhida para um membro da guilda, com CSRF e validação dos dois IDs. |

A estrutura atual de `quests` usa `dungeon_id` e `data_quest`; Models, API e Services foram ajustados a esses nomes. Consulte `docs/database-status.md` para os objetos encontrados no banco.

## Feature 1: seleção de aventureiro (protótipo anterior em `/aventureiros`)

A página `/aventureiros` consulta os aventureiros e suas guildas no schema `rpg_trabalho_bd`. Oferece busca por nickname, filtro de classe, paginação e escolha de personagem salva na sessão. As dez classes originais têm uma aparência provisória. Essa escolha não autentica o usuário nem modifica os registros do banco.

O fluxo de escolha de personagem foi substituído na home pelo gerenciamento da guilda. A rota anterior foi mantida para preservar o trabalho já feito.

## Executar

Requisitos: PHP 8.2 ou superior com `pdo_pgsql`, `mbstring`, `openssl`, `fileinfo`, `curl` e `zip`; Composer; Node.js compatível com o Vite 7.

```powershell
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
```

Configure no `.env` as credenciais do banco original: `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` e `DB_SSLMODE`. As models já apontam para o schema `rpg_trabalho_bd`. Use `SESSION_DRIVER=file`, `CACHE_STORE=file` e `QUEUE_CONNECTION=sync` para esta etapa.

```powershell
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Abra http://127.0.0.1:8000. Para desenvolver com atualização dos assets, execute `npm run dev` em outro terminal. No ambiente preparado nesta máquina, o PHP portátil está em `.tools/php/php.exe` e o Composer em `.tools/composer.phar`; essa pasta é local e ignorada pelo Git.

## Rotas desta feature

| Método | Rota | Função |
| --- | --- | --- |
| GET | `/aventureiros` | Lista, busca, filtra e pagina os aventureiros do protótipo anterior. |
| POST | `/aventureiro/selecionar` | Valida `aventureiro_id` e salva a escolha na sessão, com proteção CSRF. |

A API usa apenas os prefixos `/api/jogador` e `/api/admin` para os cadastros; as antigas rotas genéricas `/api/{recurso}` foram removidas. Consultas de dungeons são públicas. Jogadores só alteram seus próprios vínculos e registram suas próprias quests; administradores têm o CRUD completo. O registro de quests e o vínculo de guilda por procedures ficam em `POST /api/jogador/quests/registrar`, `POST /api/admin/quests/registrar`, `POST /api/jogador/aventureiros/{id}/guilda` e `POST /api/admin/aventureiros/{id}/guilda`.

Para acompanhar as alterações ao vivo, mantenha `php artisan serve` e `npm run dev -- --host 127.0.0.1` abertos em terminais separados.

## Testes

```powershell
php artisan test
```

É necessária a extensão `pdo_sqlite`. As suítes usam SQLite em memória com um schema anexado chamado `rpg_trabalho_bd`; não usam nem modificam o Neon. Cobrem autenticação, cadastro, limitação de tentativas, sigilo da senha, renovação/invalidação da sessão, permissões da API, listagem, filtros, paginação, escolha explícita de dungeon e regressões do painel.

Os testes HTTP usam um substituto de `ProcedureService` exclusivamente em `tests/Support` para o SQLite. A suíte separada `phpunit.postgresql.xml` executa as procedures e triggers reais em PostgreSQL local, com rollback de cada teste. Configure `COROA_TEST_LOCAL=1`, `COROA_TEST_PASSWORD` e, se necessário, `COROA_TEST_PORT`, `COROA_TEST_DATABASE` e `COROA_TEST_USER`; execute `php vendor/phpunit/phpunit/phpunit -c phpunit.postgresql.xml`. O host dessa suíte é fixo em `127.0.0.1`.

O snapshot sem dados está em `database/schema/rpg_trabalho_bd.sql`. A evidência de backup/restauração está em `docs/evidencias/restauracao.json`. O dump com dados reais permanece local em `.tools/backups/`, ignorado pelo Git.

## Aparências e futuras fotos

`config/appearance.php` associa cada classe a uma região do atlas. O componente Blade `adventurer-art` renderiza a aparência sem adicionar colunas às tabelas originais.

Para uma futura imagem própria, coloque o arquivo na pasta `public/images/portraits/` e configure o ID do aventureiro:

```php
'characters' => [
    42 => ['portrait' => 'images/portraits/pedro.webp'],
],
```

O retrato substitui apenas a imagem daquele aventureiro. Classe, nível e guilda continuam vindo do banco. Nesta entrega não há upload de fotos ou editor de skins; variações de sprites com fotos serão uma feature separada.

A origem e os prompts das imagens provisórias estão em `docs/visual-assets.md`.
