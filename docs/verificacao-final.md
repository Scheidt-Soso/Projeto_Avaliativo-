# Conferência de 04/11/2026

Atualizações de API, testes e instruções de backup da equipe incorporadas a partir da `main` em `270346f`.

## Resultados de dungeons

O jogador escolhe a dungeon e clica em **Explorar dungeon**. O servidor sorteia sucesso/falha com 50% de chance de sucesso, obtém a data do próprio banco e chama a procedure original para registrar a quest, calcular XP e atualizar o nível. O feedback mostra resultado, recompensa e nível atual. A seleção de destino continua explícita e, sozinha, não altera o progresso. Administradores mantêm o cadastro manual apenas como ferramenta de manutenção do histórico.

O jogador também pode explorar pelo perfil, mesmo sem guilda. O novo envio recebe somente dungeon e token; conta, resultado e progresso são determinados no servidor. Reenvios retornam a mesma quest sem novo sorteio ou XP duplicado, e um token não pode trocar de destino. O registro manual de resultado pelos endpoints antigos e o CRUD administrativo continuam protegidos. Campos forjados de resultado, data, conta, papel, nível e XP são rejeitados no fluxo de exploração.

Na tela inicial, **Consultar atlas → nome da dungeon** abre sucessos e falhas recentes da guilda, com data, aventureiro e acesso à quest. Os totais incluem todo o histórico dos membros atuais; os detalhes são limitados aos cinco registros mais recentes de cada dungeon. O destino selecionado mostra o último resultado específico do aventureiro escolhido. Sem quests correspondentes, a tela informa ausência de registros.

## Acesso administrativo

O administrador usa a mesma página `/login`, com o nickname e a senha da tabela original. O acesso aos cadastros depende de `role = admin`, conferido no servidor. Não é necessário cadastrar outra conta em `users`.

A conta administrativa existente possui hash bcrypt, conferido em consulta somente leitura. Sua senha não foi alterada. Como a senha em texto não foi fornecida, a entrada dessa conta específica não foi tentada: o fluxo completo foi testado com contas administrativas temporárias no PostgreSQL local restaurado.

## Validação executada

- 92 testes HTTP/unitários aprovados, com 622 verificações: autenticação, permissões, CRUD, perfil, relatórios, escolha de destino, sorteio de vitória/falha, rejeição de resultado forjado, reenvio sem duplicação e separação de resultados por conta.
- 12 testes no PostgreSQL local aprovados, com 127 verificações: procedures, triggers, cascatas, rollback, login administrativo, relatórios, histórico e expedições sorteadas usando a procedure real e a data do banco.
- Checagem HTTP com sessão e CSRF na cópia local: exploração com gerador real, feedback de resultado e repetição do envio sem criar uma segunda quest.
- Build de produção aprovado.
- As contas de teste são revertidas; os testes não gravam no Neon.

## Limites e próximos cuidados

Esta conferência valida as respostas HTTP e o comportamento do banco. Os seletores foram substituídos por listas pesquisáveis em preto e branco, com suporte a teclado e cores explícitas no fallback nativo. A revisão visual automática pelo Computer Use foi interrompida porque a ferramenta não conseguiu confirmar a URL do navegador; a validação visual em diferentes tamanhos de tela continua pendente.

O ambiente usa o schema original já existente. Não execute migrations ou restauração sobre o banco compartilhado para iniciar o site. Para uma instalação em banco vazio, o arquivo único `SQL/projeto_completo.sql` agora prepara toda a estrutura e os dados, sem senhas/tokens/sessões. O snapshot antigo em `database/schema/rpg_trabalho_bd.sql` continua sendo uma exportação apenas de estrutura para `psql`, não uma migration Laravel.

O relatório acadêmico em PDF e a apresentação/defesa ainda precisam ser finalizados separadamente. A evidência sanitizada de restauração já está em `docs/evidencias/restauracao.json`; o dump com dados reais permanece local, fora do Git.
