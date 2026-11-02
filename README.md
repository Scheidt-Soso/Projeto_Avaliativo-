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

O painel e o protótipo de seleção exigem login. A API usa a mesma sessão por Sanctum para chamadas do próprio site; `/api/user` devolve a conta autenticada sem a senha. Na aplicação, `jogador` pode consultar os cadastros e `admin` pode criar, editar e excluir. O papel vem do banco, que aceita somente esses dois valores. Não há promoção de usuário pelo cadastro público. Para desenvolvimento em outro domínio/porta, configure `SANCTUM_STATEFUL_DOMAINS` e envie os cookies e o token CSRF.

## Feature 2: quartel de guildas em preto e branco

A home agora é o painel da guilda: troca de guilda, membros com fichas, total de expedições, taxa de sucesso, XP conquistado e últimas quests. Todos os dados vêm das tabelas originais. O XP do painel soma as recompensas atuais das dungeons das quests bem-sucedidas dos membros atuais da guilda.

O quartel é desenhado e animado em canvas, com parallax, patrulheiros, bandeiras, tochas, portal e partículas. A tipografia VT323 é servida localmente, com sua licença OFL. Há pausa manual de animações e respeito a `prefers-reduced-motion`.

Cada guilda original possui nome de quartel, região e mensagem próprios, definidos em `config/guild-bases.php`. A cena combina oito biomas, quatro arquiteturas, estandartes e marcos locais: neve, chuva, brasa, folhas ou poeira astral. O cenário é estável para a mesma guilda; bases vazias não recebem patrulheiros fictícios. Guildas novas recebem um perfil padrão. A identidade visual não altera o banco.

O elenco permite buscar por nome, combinar a classe e ordenar por nível, nome ou número de expedições. Os filtros atuam nos membros já carregados e não alteram os totais do painel nem a lista de envio para expedições. Sem JavaScript, a listagem completa continua disponível.

O botão **Consultar atlas** mostra as dungeons do banco com dificuldade, recompensa e vitórias/tentativas da guilda atual. É possível buscar, filtrar dificuldade, ordenar por XP e destacar destinos ainda não explorados. Esses números descrevem o histórico, não a chance de vitória futura.

A preparação exige escolher explicitamente um aventureiro da guilda e uma dungeon cadastrada, pelo formulário ou pelo atlas. O servidor valida os IDs e guarda somente a seleção na sessão; ao abrir o painel, consulta novamente os dados atuais. **Escolher um destino não registra uma quest nem muda o nível.** O banco não define sorteio ou probabilidade de vitória. O registro da quest com a procedure original será uma próxima feature. Nenhum objeto ou dado do Neon foi alterado nesta etapa.

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

As rotas REST de `/api/aventureiros`, `/api/guildas`, `/api/dungeons` e `/api/quests` exigem autenticação; alterações exigem papel `admin`.

Para acompanhar as alterações ao vivo, mantenha `php artisan serve` e `npm run dev -- --host 127.0.0.1` abertos em terminais separados.

## Testes

```powershell
php artisan test
```

É necessária a extensão `pdo_sqlite`. As suítes usam SQLite em memória com um schema anexado chamado `rpg_trabalho_bd`; não usam nem modificam o Neon. Cobrem autenticação, cadastro, limitação de tentativas, sigilo da senha, renovação/invalidação da sessão, permissões da API, listagem, filtros, paginação, escolha explícita de dungeon e regressões do painel.

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
