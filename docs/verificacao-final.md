# Conferência de 04/11/2026

Atualizações de API, testes e instruções de backup da equipe incorporadas a partir da `main` em `270346f`.

## Resultados de dungeons

O administrador registra sucesso ou falha em **Quests → Novo registro**, pelo atalho no quartel ou pela API administrativa. A procedure original calcula XP e nível. A seleção de destino continua explícita e não altera o progresso.

O jogador consulta dungeons e histórico, prepara seu próprio destino e vincula sua conta a uma guilda compatível. Não há formulário de resultado no perfil. Tentativas de gravação pelo perfil, pelo cadastro web, pelos endpoints antigos de jogador ou pela API administrativa são bloqueadas antes de alterar o banco. Campos forjados de papel, nível e XP não concedem permissões.

Na tela inicial, **Consultar atlas → nome da dungeon** abre sucessos e falhas recentes da guilda, com data, aventureiro e acesso à quest. Os totais incluem todo o histórico dos membros atuais; os detalhes são limitados aos cinco registros mais recentes de cada dungeon. O destino selecionado mostra o último resultado específico do aventureiro escolhido. Sem quests correspondentes, a tela informa ausência de registros.

## Acesso administrativo

O administrador usa a mesma página `/login`, com o nickname e a senha da tabela original. O acesso aos cadastros depende de `role = admin`, conferido no servidor. Não é necessário cadastrar outra conta em `users`.

A conta administrativa existente possui hash bcrypt, conferido em consulta somente leitura. Sua senha não foi alterada. Como a senha em texto não foi fornecida, a entrada dessa conta específica não foi tentada: o fluxo completo foi testado com contas administrativas temporárias no PostgreSQL local restaurado.

## Validação executada

- 83 testes HTTP/unitários aprovados, com 546 verificações: autenticação, permissões, CRUD, perfil, relatórios, seleção de destino e exibição dos resultados corretos por dungeon, aventureiro e guilda.
- 11 testes no PostgreSQL local aprovados, com 112 verificações: procedures, triggers, cascatas, rollback, login administrativo web/API, acesso aos quatro cadastros e cinco relatórios, registro de sucesso/falha, logout, bloqueio de gravação por jogador e limite de cinco resultados recentes por dungeon.
- Build de produção aprovado.
- As contas de teste são revertidas; os testes não gravam no Neon.

## Limites e próximos cuidados

Esta conferência valida as respostas HTTP e o comportamento do banco. Não substitui a revisão visual no navegador em diferentes tamanhos de tela.

O ambiente usa o schema original já existente. Não execute migrations ou restauração sobre o banco compartilhado para iniciar o site; a instalação em um banco vazio exige preparar esse schema separadamente. O snapshot em `database/schema/rpg_trabalho_bd.sql` é uma exportação para `psql`, não uma migration Laravel.

O relatório acadêmico em PDF e a apresentação/defesa ainda precisam ser finalizados separadamente. A evidência sanitizada de restauração já está em `docs/evidencias/restauracao.json`; o dump com dados reais permanece local, fora do Git.
