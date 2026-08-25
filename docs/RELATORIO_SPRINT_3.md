# Relatório — Login do professor e ficha SMER em PDF

**Período:** 24 de agosto de 2026
**Branch:** `Paulo`
**Itens do backlog atacados:** AJ-01, AJ-02, AJ-06 e a geração automática do termo

---

## Decisões

**Escopo fechado antes de codar.** Do backlog de ajustes, entraram AJ-01 (login), AJ-02 (bug da data), AJ-06 (anexo do documento) e a geração do termo. Ficaram de fora, por decisão explícita: AJ-04 (dados reais do rodapé, que dependem de informação que não temos), AJ-07 (responsividade da tabela no mobile) e AJ-08 (redirect pós-matrícula). AJ-03 já estava pronto desde o commit `9d76dbf`.

**PDF gerado no servidor, com dompdf.** A alternativa era uma página HTML com `@media print` e o usuário salvando via Ctrl+P. Descartada: o resultado dependeria do navegador de quem imprime, e a ficha precisa sair idêntica ao papel que a Secretaria aceita.

**Login por CPF, não por e-mail.** É o identificador que o professor já usa e sabe de cor. `users` ganhou uma coluna `cpf` única e a autenticação passou a ser `Auth::attempt(['cpf' => ..., 'password' => ...])`.

**A ficha é entregue pelo professor, não baixada pelo responsável.** Decisão sua, e é o que casa com o fluxo real: na primeira aula o aluno leva a folha impressa, devolve assinada na aula seguinte. Por isso o botão "Gerar ficha" vive no dashboard e não na tela de sucesso da matrícula.

**Checkbox de filiação em vez de campo opcional.** Aluno sem pai ou sem mãe registrada é comum em projeto social. Deixar o campo simplesmente opcional produziria fichas com lacunas indistinguíveis de esquecimento. Com o checkbox, a ficha imprime "Não declarado" — fica registrado que houve declaração do responsável. Regra de fecho: não é possível marcar os dois, senão o documento não identifica ninguém.

**Colunas novas entram `nullable` no banco.** As tabelas já têm linhas em produção; `NOT NULL` quebraria a migration. A obrigatoriedade vive na validação do formulário. O preço: alunos cadastrados antes desta mudança geram ficha com lacunas.

**PDF gerado sob demanda, nunca gravado em disco.** Corrigir um dado do aluno e reimprimir sempre produz a versão atual.

**Um controller, contrariando o CLAUDE.md de propósito.** O projeto mapeia rotas direto para componentes Livewire. Download de arquivo binário não é página Livewire: uma URL estável abre em nova aba, sobrevive a refresh e é testável com `$this->get(...)`. A exceção foi registrada no CLAUDE.md em vez de ficar contradizendo o código.

**Ficha assinada em disco privado.** O arquivo tem RG, CPF e endereço de menor de idade. Vai para o disco `local`, não o `public`, e só sai por rota autenticada.

---

## Processos

O trabalho seguiu quatro etapas, cada uma com aprovação antes da seguinte:

1. **Brainstorming** — classificação do tamanho da tarefa, perguntas sobre as decisões que mudariam o resultado, e um desenho apresentado para revisão.
2. **Spec** — `docs/superpowers/specs/2026-08-24-login-e-ficha-pdf-design.md`, com o mapeamento campo a campo da ficha de papel para o banco, os não-objetivos e os riscos assumidos.
3. **Plano** — `docs/superpowers/plans/2026-08-24-login-e-ficha-pdf.md`: 15 tarefas, 125 passos, cada um com o código exato e o comando de verificação.
4. **Execução em TDD** — para cada tarefa: escrever o teste, rodar e **ver a falha**, implementar o mínimo, rodar e ver passar, `pint`, commit.

Ver o teste falhar antes de implementar não é cerimônia. Na Task 11, os três testes de faixa etária passaram já no primeiro `RED` — foi isso que provou que o bug da data era exclusivamente de front-end e que a correção teria de ser verificada no navegador, não no PHPUnit.

Commits foram deixados para você executar manualmente, com mensagem e arquivos sugeridos ao final de cada tarefa.

---

## Dificuldades

**A ficha exigia campos que o sistema não tinha.** Cruzando a foto do documento com o schema, faltavam escola, série, filiação do pai, filiação da mãe, bairro, telefone residencial, celular do aluno, e-mail do aluno e — o mais fácil de passar batido — o **RG do responsável**, que a autorização cita em "portador da Cédula de Identidade RG nº". Nove colunas novas.

**Migrar tabela com dados em produção.** Toda coluna obrigatória teve de entrar `nullable`, com a obrigatoriedade movida para a camada de validação.

**Um teste antigo afirmava o oposto de uma decisão nova.** `test_student_rg_is_optional` garantia que o RG podia ficar vazio. Com a ficha, ele passou a ser obrigatório. O teste foi substituído por `test_student_rg_is_required`, com um comentário explicando o porquê da inversão.

**`required_unless` do Laravel não serve para booleano do Livewire.** A regra compara o valor com a string `"true"` do parâmetro — uma coerção frágil. A obrigatoriedade condicional da filiação foi resolvida em PHP, decidindo o array de regras antes de validar.

**`nullable` não ignora string vazia.** Os campos mascarados chegam como `''`, e `nullable` só pula `null`. Sem tratar isso, um telefone residencial em branco falharia no `regex`. Resolvido normalizando `''` para `null` antes de validar.

---

## Problemas

**A suíte de testes rodava contra o banco de produção.** O CLAUDE.md afirmava que os testes usavam SQLite via `.env.testing`. Esse arquivo **não existia**. Na prática, `DEFAULT=pgsql HOST=ep-broad-smoke-acb19elt.sa-east-1.aws.neon.tech` — cada teste pagava latência de rede até São Paulo, e o `RefreshDatabase` rodava contra a infraestrutura real. O primeiro teste levava 8,4 segundos.

**`artisan test` abortava sem argumentos.** O `phpunit.xml` apontava para `tests/Unit`, um diretório que não existia.

**Cypress nunca tinha rodado.** O `baseUrl` apontava para uma URL de GitHub Codespaces expirada; três testes esperavam a rota `/matricula`, que virou `/enrollment`; `landing.cy.js` tinha `http://localhost:8000` cravado no código, ignorando o `baseUrl`; o binário do Cypress sequer estava instalado na máquina; e `cypress/videos` pertencia a `root`, com um vídeo de 2,3 MB versionado no repositório.

**Dois testes da landing page estavam desatualizados.** Um procurava o texto "Inscrever-se" quando o header diz "Matricule-se"; o outro clicava num link que só existe no menu mobile, invisível no desktop.

**Um teste de faixa etária era flaky por hora do dia.** Ele montava a data com `toISOString()`, que converte para UTC. No fuso do Brasil, depois das 21h isso adianta um dia — a data de corte virava válida e o teste falhava. Passava de manhã, falhava à noite.

**Nome de arquivo colidia em uploads no mesmo segundo.** O anexo da ficha assinada usava `now()->timestamp` no nome. Dois envios dentro do mesmo segundo produziam o mesmo caminho, e o teste de substituição pegou isso.

**A chave de produção quase foi para o repositório.** O plano mandava copiar a `APP_KEY` do `.env` para o `.env.testing`. Como esse arquivo passou a ser versionado (para o Vitor também ter), isso publicaria a chave da aplicação.

---

## Soluções

| Problema | Solução | Onde |
|---|---|---|
| Testes contra o Neon | `.env.testing` com SQLite; overrides de banco removidos do `phpunit.xml` | `.env.testing`, `phpunit.xml` |
| `artisan test` abortando | `tests/Unit/` criado | `tests/Unit/.gitkeep` |
| Cypress inexecutável | `baseUrl` para `http://localhost`, rota corrigida, binário instalado, vídeos desligados e ignorados pelo git | `cypress.config.js`, `.gitignore` |
| Testes da landing desatualizados | Asserções alinhadas ao que a página realmente mostra | `cypress/e2e/landing.cy.js` |
| Teste flaky por fuso | Helpers `isoLocal()` e `anosAtras()`, que montam a data em horário local | `cypress/e2e/enrollment.cy.js` |
| Colisão de nome de arquivo | Sufixo aleatório com `Str::random(8)` no lugar do timestamp | `app/Livewire/Admin/Dashboard.php` |
| Chave de produção exposta | Chave descartável gerada só para o ambiente de teste | `.env.testing` |
| Campo de data se apagando | Handler `x-on:change` removido dos dois inputs; `min` alinhado à regra do servidor | `enrollment-form.blade.php` |
| Formatação duplicada | Closures do dashboard extraídas para uma classe compartilhada com a ficha | `app/Support/Formatters.php` |

---

## Resultados

**94 testes PHPUnit passando** (273 asserções), contra 37 no início — 57 testes novos. A suíte inteira roda em **5,4 segundos**; antes, dez testes sozinhos levavam 17.

**26 testes Cypress passando** em quatro specs, contra zero — a suíte E2E não era executável.

### O que passou a existir

- **AJ-01 — Login do professor.** Autenticação por CPF e senha em `/login`, com rate limiting de 5 tentativas por CPF+IP e mensagem genérica que não revela se o CPF existe. `/admin/dashboard` e as rotas de ficha exigem sessão; visitante recebe 302 para o login. O `TODO` que estava no arquivo de rotas foi removido. Foi este o furo que o teste de usuário expôs, com o P2 entrando na área restrita sem permissão.
- **Geração automática do termo.** Botão "Gerar ficha" em cada aluno do dashboard baixa a Ficha de Cadastro de Atleta no formato da SMER, preenchida — restam só as duas assinaturas. O título acompanha a modalidade do aluno, então serve para Boxe, Jiu Jitsu, Muay Thai e Taekwondo.
- **AJ-02 — Bug da data de nascimento.** Corrigido e verificado no navegador real. De quebra, o `min` do campo do aluno passou a concordar com a regra do servidor: um aluno de 17 anos e meio deixou de ser recusado indevidamente.
- **AJ-06 — Anexo do documento.** O professor sobe o PDF ou a foto da ficha assinada pelo modal do aluno (até 5 MB, PDF/JPG/PNG). O status vai para "assinado" sozinho, o arquivo fica em disco privado e volta pelo download com o nome original.
- **Formulário ampliado.** Onze campos novos, com as máscaras e os `data-cy` no padrão que já existia, e o checkbox de filiação inexistente.
- **Dashboard completo.** O modal de detalhes passou a mostrar RG, telefone residencial, bairro, escola, série, filiação, celular e e-mail do aluno.

### O que ficou de fora

Por decisão de escopo: **AJ-04** (dados reais do rodapé — dependem de endereço, telefone e redes do CTM), **AJ-07** (responsividade da tabela no mobile, hoje apenas com scroll horizontal) e **AJ-08** (redirect automático após a matrícula).

Por decisão de projeto, registrada no spec: assinatura digital, envio da ficha por e-mail e tela de cadastro de professor.

**Pendência de material:** o cabeçalho do PDF usa `public/img/prudentopolis-smer.png` se o arquivo existir, e cai num cabeçalho montado em HTML enquanto não existir. Basta salvar a arte oficial nesse caminho — nenhuma alteração de código é necessária.

### Antes do deploy

Falta, do roadmap: os itens AJ-04, AJ-07 e AJ-08; a aquisição do domínio; e o deploy em si. Duas providências de segurança são obrigatórias antes do ar: trocar `PROFESSOR_PASSWORD` no `.env` de produção (o padrão `heroisdotatame` é só de desenvolvimento) e rodar as três migrations novas.
