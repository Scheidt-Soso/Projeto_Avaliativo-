# Coroa de Cinzas

Aplicação do projeto bimestral de Banco de Dados II e Back-end, com Laravel 12, Blade, PostgreSQL no Neon e Vite/Tailwind. O produto agora é um gerenciador de guildas, centrado no banco original de guildas, aventureiros, dungeons e quests.

## Feature 2: quartel de guildas em preto e branco

A home agora é o painel da guilda: troca de guilda, membros com fichas, total de expedições, taxa de sucesso, XP conquistado e últimas quests. Todos os dados vêm das tabelas originais. O XP do painel soma as recompensas atuais das dungeons das quests bem-sucedidas dos membros atuais da guilda.

O quartel é desenhado e animado em canvas, com parallax, patrulheiros, bandeiras, tochas, portal e partículas. A tipografia VT323 é servida localmente, com sua licença OFL. Há pausa manual de animações e respeito a `prefers-reduced-motion`.

Cada guilda original possui nome de quartel, região e mensagem próprios, definidos em `config/guild-bases.php`. A cena combina oito biomas, quatro arquiteturas, estandartes e marcos locais: neve, chuva, brasa, folhas ou poeira astral. O cenário é estável para a mesma guilda; bases vazias não recebem patrulheiros fictícios. Guildas novas recebem um perfil padrão. A identidade visual não altera o banco.

O sorteio escolhe uma dungeon real no servidor, exige um membro da guilda e guarda o destino na sessão. **Sortear não registra uma quest nem muda o nível.** A execução da expedição com a procedure original será uma próxima feature, depois de fechar as regras de sucesso e de progressão. Nenhum objeto ou dado do Neon foi alterado nesta etapa.

| Método | Rota | Função |
| --- | --- | --- |
| GET | `/?guilda=1` | Painel da guilda e histórico real. Sem parâmetro, abre a primeira guilda. |
| POST | `/guildas/{guilda}/expedicao/sortear` | Sorteia uma dungeon para um membro da guilda, com CSRF e validação de pertença. |

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

As rotas REST existentes de `/api/aventureiros`, `/api/guildas`, `/api/dungeons` e `/api/quests` foram preservadas.

Para acompanhar as alterações ao vivo, mantenha `php artisan serve` e `npm run dev -- --host 127.0.0.1` abertos em terminais separados.

## Testes

```powershell
php artisan test
```

É necessária a extensão `pdo_sqlite`. A suíte de seleção usa exclusivamente SQLite em memória com um schema anexado chamado `rpg_trabalho_bd`; não usa nem modifica o Neon. Verifica listagem, busca combinada com classe, curingas literais, paginação, seleção persistida, rejeição de IDs inválidos, retrato independente da classe e regressões de consulta da API.

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
