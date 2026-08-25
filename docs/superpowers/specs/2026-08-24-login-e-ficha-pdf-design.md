# Login do professor e geração da ficha de cadastro em PDF

- **Data:** 2026-08-24
- **Status:** aprovado para implementação
- **Escopo:** autenticação por CPF para o professor, ampliação do cadastro de matrícula e geração automática do PDF da "Ficha de Cadastro de Atleta" da SMER / Prudentópolis

## Problema

O projeto tem duas pendências que se conectam:

1. `/admin/dashboard` está aberto para qualquer visitante. O [routes/web.php](../../../routes/web.php) carrega um `TODO: proteger com middleware 'auth' quando o login for implementado`, e `/login` é apenas um `redirect('/')`. Não existe usuário professor nem forma de criar um.
2. A SMER exige uma ficha de papel assinada — "O atleta só poderá participar dos treinos após entregar a ficha preenchida e assinada ao professor". Hoje o professor preencheria essa ficha à mão para cada aluno, mesmo já tendo os dados no sistema.

O fluxo real da secretaria: na primeira aula o professor entrega a folha impressa ao aluno, que devolve assinada na aula seguinte. Logo o PDF precisa sair **pronto para assinatura** — todos os campos preenchidos, sobrando apenas as duas linhas de assinatura.

O obstáculo é que o schema atual não guarda a maior parte dos campos da ficha.

## Objetivos

- Professor autentica por CPF e senha; `/admin/dashboard` fica inacessível a visitantes.
- O formulário de matrícula coleta todos os dados que a ficha da SMER exige.
- O professor gera e imprime a ficha de qualquer aluno em um clique, a partir do dashboard.
- Aluno sem pai ou sem mãe registrada consegue se matricular sem improvisar dados.

## Não-objetivos

- Assinatura digital ou eletrônica — a ficha é assinada à caneta, por exigência da SMER.
- Upload do PDF assinado de volta ao sistema. A coluna `students.termo_arquivo` já existe e fica reservada para isso, mas não é usada aqui.
- Envio do PDF por e-mail (exigiria SMTP configurado).
- Autoatendimento de download pelo responsável ao concluir a matrícula — o fluxo acordado é o professor entregar a folha em mãos.
- Papéis/permissões. Existe um único tipo de usuário: o professor. Não há coluna `role`.
- Recuperação de senha. A senha é trocada pelo `.env` + reseed.

---

## 1. Mapeamento da ficha para o schema

Cruzando a ficha de papel com o schema atual (`responsibles`, `students`):

| Campo da ficha | Coluna | Situação | Obrigatório |
|---|---|---|---|
| Nome do Atleta | `students.name` | existe | sim |
| R.G. (atleta) | `students.rg` | existe, hoje `nullable` | **passa a sim** |
| Data Nasc. | `students.birth_date` | existe | sim |
| Escola | `students.school` | **novo** | sim |
| Série | `students.grade` | **novo** | sim |
| Filiação Pai | `students.father_name` | **novo** | condicional (§3) |
| Mãe | `students.mother_name` | **novo** | condicional (§3) |
| Cel atleta | `students.phone` | **novo** | não |
| e-mail atleta | `students.email` | **novo** | não |
| Endereço | `responsibles.address` | existe | sim |
| Bairro | `responsibles.neighborhood` | **novo** | sim |
| Fone resid. | `responsibles.home_phone` | **novo** | não |
| Cel pais | `responsibles.phone_number` | existe | sim |
| e-mail Pais | `responsibles.email` | existe | sim |
| RG do responsável (autorização) | `responsibles.rg` | **novo** | sim |

Campos que a ficha não usa, mas o sistema já coleta e continuam existindo: `students.cpf`, `responsibles.cpf`, `responsibles.birth_date`, `students.modalidade`.

Duas colunas de controle acompanham a filiação condicional: `students.no_father` e `students.no_mother` (booleanos, default `false`). Guardar a declaração explícita distingue "responsável declarou que não há" de "campo ficou vazio por engano".

### Migration

Arquivo único, `add_ficha_fields_to_responsibles_and_students_table`:

- `responsibles`: `rg` (string 20), `neighborhood` (string 80), `home_phone` (string 11, nullable).
- `students`: `school` (string 120), `grade` (string 30), `father_name` (string 80, nullable), `mother_name` (string 80, nullable), `no_father` (boolean, default false), `no_mother` (boolean, default false), `phone` (string 11, nullable), `email` (string, nullable).

**Compatibilidade com dados existentes.** Colunas novas obrigatórias não podem entrar como `NOT NULL` numa tabela que já tem linhas. Cada uma entra `nullable` no banco; a obrigatoriedade é imposta na validação do formulário. `students.rg` permanece `nullable` no banco pelo mesmo motivo — cadastros anteriores à mudança podem não ter RG.

Consequência assumida: a ficha de um aluno cadastrado antes desta mudança sai com lacunas. Aceitável — é um punhado de registros de teste, e o professor completa à mão ou o responsável refaz o cadastro.

Os dois modelos ganham as novas colunas em `$fillable`; `Student` ganha `no_father` e `no_mother` como `'boolean'` em `casts()`.

---

## 2. Login do professor

### Autenticação por CPF

`users` ganha `cpf` (string 11, único, nullable — nullable porque a tabela pode ter linhas de teste). `User` passa a expor `cpf` no atributo `#[Fillable]`.

`Auth::attempt(['cpf' => $cpf, 'password' => $senha], $remember)` funciona sem configuração extra: o `EloquentUserProvider` monta o `where` a partir das chaves passadas e trata `password` à parte. Não é preciso mexer em `config/auth.php`.

### Componente

`App\Livewire\Auth\Login`, rota `GET /login` (substitui o `redirect('/')`), nome de rota `login` preservado — o middleware `auth` do Laravel redireciona para ele por convenção.

- Propriedades: `cpf`, `password`, `remember`.
- Campo de CPF reaproveita a máscara Alpine já usada em [enrollment-form.blade.php](../../../resources/views/livewire/enrollment-form.blade.php) — grava 11 dígitos crus em `$wire`, exibe formatado.
- Layout próprio `layouts/auth.blade.php`: cartão centrado, sem o header público de "Matricule-se", seguindo a paleta preto/neutral já estabelecida.
- Em caso de sucesso: `session()->regenerate()` e redirect para `route('admin.dashboard')`.
- Em caso de falha: uma única mensagem genérica, *"CPF ou senha inválidos."*, sem revelar se o CPF existe.

### Rate limiting

`RateLimiter` com chave `login:{cpf}|{ip}`, 5 tentativas, bloqueio de 60 segundos. Ao estourar, mensagem com os segundos restantes. O contador é limpo no login bem-sucedido. Impede força bruta contra um espaço de busca pequeno (um único usuário conhecido).

### Rotas protegidas

`/admin/dashboard` entra no grupo `Route::middleware('auth')`, junto do `/logout` que já está lá. O `TODO` do arquivo de rotas é removido. `/` e `/enrollment` permanecem públicos — a matrícula é feita pelo responsável, sem conta.

### Seeder

`ProfessorSeeder`, invocado por `DatabaseSeeder`, faz `updateOrCreate` pelo CPF para ser idempotente:

| Variável de ambiente | Fallback |
|---|---|
| `PROFESSOR_CPF` | `12345678909` (CPF fake, válido nos dígitos verificadores) |
| `PROFESSOR_PASSWORD` | `heroisdotatame` |
| `PROFESSOR_NAME` | `Professor` |
| `PROFESSOR_EMAIL` | `professor@heroisdotatame.local` |

As quatro entram no `.env.example` documentadas. Trocar a senha em produção é editar o `.env` e rodar `db:seed --class=ProfessorSeeder`. O `README.md` ganha essa instrução e o aviso de que a senha padrão só serve para desenvolvimento.

---

## 3. Filiação condicional

Regra de negócio: **pelo menos uma filiação precisa constar na ficha.** A autorização identifica o menor pela filiação; sem nenhuma das duas, o documento não serve.

Estado no componente: `student_father_name`, `student_no_father`, `student_mother_name`, `student_no_mother`.

Interface — cada linha de filiação é um input com um checkbox abaixo:

- `Não possui pai registrado` / `Não possui mãe registrada`
- Marcar o checkbox limpa e desabilita o input via Alpine, sem round-trip ao servidor.
- Desmarcar reabilita o input vazio.

Validação:

| Regra | Mensagem |
|---|---|
| `student_father_name` → `required_unless:student_no_father,true` | "Informe a filiação do pai ou marque que não possui." |
| `student_mother_name` → `required_unless:student_no_mother,true` | "Informe a filiação da mãe ou marque que não possui." |
| ambos os checkboxes marcados | "É preciso informar pelo menos uma filiação." |

A regra de "pelo menos uma" é uma validação de fecho no componente (`addError` em `student_no_father` após o `validate()`), porque depende de dois campos ao mesmo tempo.

Persistência: com o checkbox marcado grava-se `null` no nome e `true` no booleano.

Na ficha, uma filiação declarada como inexistente imprime `Não declarado` em vez de linha vazia — deixa explícito para a SMER que foi declaração do responsável, não campo esquecido.

---

## 4. Geração do PDF

### Dependência

`composer require barryvdh/laravel-dompdf`. Auto-discovery cuida do registro; nenhuma configuração precisa ser publicada. Motivo da escolha: PHP puro, sem binário externo nem headless browser, e o layout da ficha é tabular simples — bem dentro do que o dompdf suporta.

### Rota e controller

```
GET /admin/alunos/{student}/ficha   →   StudentFichaController (invocável)
```

Dentro do grupo `auth`, nome `admin.students.ficha`. Route-model binding por UUID.

`App\Http\Controllers\StudentFichaController` carrega o aluno com `responsible`, renderiza `resources/views/pdf/ficha-atleta.blade.php` e devolve `$pdf->download("ficha-{slug-do-nome}.pdf")`.

**Desvio deliberado do CLAUDE.md.** O documento afirma que rotas mapeiam direto para componentes Livewire, sem controllers. Um download de arquivo binário não é uma página Livewire; uma URL estável abre em nova aba, sobrevive a refresh e é trivial de testar com `$this->get(...)`. O CLAUDE.md será atualizado para registrar a exceção junto com sua justificativa, em vez de ficar contradizendo o código.

Nada é gravado em disco. O PDF é montado sob demanda, então corrigir um dado do aluno e reimprimir sempre produz a versão atual.

### Botão no dashboard

Uma coluna "Ficha" na tabela de [dashboard.blade.php](../../../resources/views/livewire/admin/dashboard.blade.php), com link "Gerar ficha" apontando para a rota, `target="_blank"`.

Detalhe necessário: a linha inteira já tem `@click="select(...)"` que abre o modal de detalhes. O link precisa de `@click.stop` para não disparar o modal junto. O mesmo botão aparece dentro do modal, onde não há conflito.

### Layout da ficha

`resources/views/pdf/ficha-atleta.blade.php`, A4 retrato, CSS inline (dompdf não processa Tailwind), reproduzindo a ficha de papel:

1. **Cabeçalho** — `MUNICÍPIO DE PRUDENTÓPOLIS` e faixa `SECRETARIA DE ESPORTES E RECREAÇÃO`, montados em HTML/CSS imitando a arte original. O espaço para o brasão do município e o logo da SMER fica reservado com dimensões fixas; quando os PNGs forem fornecidos, é substituir por `<img>` sem mexer no resto do layout.
2. **Títulos** — `FICHA DE CADASTRO DE ATLETA` e, abaixo, `{MODALIDADE} – {ano}` derivados de `$student->modalidade` (em maiúsculas) e do ano corrente. Serve às quatro modalidades, não só Boxe.
3. **Tabela DADOS DO ATLETA** — mesma disposição de células da ficha original, com os campos do §1 preenchidos.
4. **AUTORIZAÇÃO DE PARTICIPAÇÃO** — texto idêntico ao da ficha, com nome e RG do responsável e nome e RG do aluno interpolados nas lacunas.
5. **Data** — `Prudentópolis, {dia} de {mês por extenso} de {ano}`, com a data de emissão do PDF. Preenchida, e não em branco, porque o pedido é que ao responsável reste apenas assinar.
6. **Assinaturas** — duas linhas em branco, `Assinatura do pai ou responsável` e `Assinatura do atleta`.
7. **Observação** — o rodapé "O atleta só poderá participar dos treinos após entregar a ficha preenchida e assinada ao professor."

CPF, telefones e datas saem formatados (`123.456.789-01`, `(42) 9 9999-9999`, `dd/mm/aaaa`). Campos opcionais vazios imprimem linha em branco; filiação declarada inexistente imprime `Não declarado`.

Os helpers de formatação hoje vivem como closures dentro do `@php` do dashboard. Vão para `app/Support/Formatters.php` como funções estáticas, usadas pelo dashboard e pela ficha — evita a segunda cópia das mesmas regexes.

---

## 5. Formulário de matrícula

Os campos novos entram nos dois cards existentes, sem reestruturar a página:

- **Dados do Responsável:** RG (máscara de 9 dígitos, igual à do RG do aluno), Bairro, Fone residencial (opcional, máscara de telefone existente).
- **Dados do Aluno:** Escola, Série, Filiação Pai + checkbox, Mãe + checkbox, Cel do atleta (opcional), E-mail do atleta (opcional). O RG perde o rótulo `(opcional)` e ganha o asterisco.

Padrões preservados: atributos `data-cy` em todo input e mensagem de erro (o Cypress depende deles), classe de borda vermelha condicional ao erro, blocos `@error`, máscaras Alpine com `x-effect` + `$wire.set`.

Regras e mensagens em português vão para `StoreEnrollmentRequest::enrollmentRules()` e `::enrollmentMessages()`, junto das existentes. `EnrollmentForm::submit()` repassa os campos novos aos dois `create()` dentro da transação já existente.

O texto do aceite LGPD passa a mencionar a finalidade real: geração da ficha de cadastro para a Secretaria Municipal de Esportes e Recreação.

---

## 6. Testes

TDD: teste antes da implementação de cada bloco.

**`tests/Feature/Auth/LoginTest.php`**
- professor loga com CPF e senha corretos e cai no dashboard
- senha errada não autentica e mostra a mensagem genérica
- CPF inexistente não autentica e mostra a mesma mensagem
- sexta tentativa consecutiva é bloqueada pelo rate limiter
- logout encerra a sessão

**`tests/Feature/Admin/DashboardTest.php`** (existente, ampliado)
- visitante em `/admin/dashboard` é redirecionado para `/login`
- usuário autenticado recebe 200

**`tests/Feature/StudentFichaTest.php`**
- visitante na rota da ficha é redirecionado para `/login`
- autenticado recebe 200 com `Content-Type: application/pdf`
- a view da ficha renderiza nome, RG, escola, série, filiação, bairro e a modalidade correta no título
- filiação declarada inexistente imprime `Não declarado`

**`tests/Feature/EnrollmentTest.php`** (existente, ampliado)
- matrícula completa persiste todos os campos novos
- cada campo obrigatório novo, faltando, produz erro de validação
- checkbox de filiação marcado dispensa o nome e grava `null` + `true`
- os dois checkboxes marcados produzem o erro de "pelo menos uma filiação"

**`cypress/e2e/enrollment.cy.js`** (existente, ampliado) — preenchimento dos campos novos e interação dos checkboxes de filiação.

Os testes criam o professor com `User::factory()`, que ganha `cpf` (gerado pelo Faker) entre seus atributos — nenhum teste depende do `ProfessorSeeder`. O seeder tem um teste próprio em `tests/Feature/ProfessorSeederTest.php`: rodá-lo duas vezes cria um único usuário (idempotência do `updateOrCreate`) e respeita `PROFESSOR_CPF` quando definido.

## Ordem de implementação

1. Migration + models + factories (base de tudo)
2. Login: coluna `cpf`, componente, layout, seeder, rotas protegidas
3. Formulário: campos novos, validação, filiação condicional
4. PDF: dependência, `Formatters`, controller, view da ficha, botão no dashboard
5. Cypress, `README.md`, `CLAUDE.md`

Os passos 3 e 4 dependem do 1; o 2 é independente dos demais.

## Riscos

| Risco | Mitigação |
|---|---|
| Registros anteriores à migration ficam sem os campos novos e geram ficha incompleta | Assumido. Colunas entram `nullable`; obrigatoriedade só na validação do formulário. São poucos registros de teste. |
| Senha padrão do seeder vazar para produção | Fallback só para desenvolvimento; `.env.example` e `README.md` avisam explicitamente que deve ser trocada. |
| dompdf renderizar acentuação incorretamente | `<meta charset="utf-8">` na view e fonte DejaVu Sans, que o dompdf embute e cobre o português. Verificado no teste de renderização. |
| Layout do PDF divergir da ficha oficial | A conferência é visual, contra a foto do documento, na etapa 4. |
