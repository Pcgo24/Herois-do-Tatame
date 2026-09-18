# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

"Herois do Tatame" (Heroes of the Mat) is a landing page for a social martial arts project serving children and adolescents aged 8-17. The UI is in Brazilian Portuguese. Stack: **TALL** (Tailwind CSS, Alpine.js, Laravel 13, Livewire 4).

## Commands

### Subir o ambiente
```bash
./vendor/bin/sail up -d
```

### Desenvolvimento (front-end com HMR)
```bash
./vendor/bin/sail npm run dev
```

### Artisan e Composer
```bash
./vendor/bin/sail artisan [comando]
./vendor/bin/sail composer [comando]
```

### Testes
```bash
./vendor/bin/sail artisan migrate:fresh --seed   # banco de dev com dados de demonstração
./vendor/bin/sail artisan test --filter TestName
touch database/testing.sqlite   # apenas na primeira vez
./vendor/bin/sail artisan serve --env=testing --port=8001
npx cypress run
```

### Linting
```bash
./vendor/bin/sail exec laravel.test vendor/bin/pint
```

## Architecture

### Request flow
Routes map directly to Livewire full-page component classes — there are no traditional controllers:
```
GET / → App\Livewire\Home → resources/views/livewire/home.blade.php
```
The layout is `resources/views/layouts/app.blade.php` (uses `$slot`, not `@yield`).

As únicas exceções são `StudentFichaController` e `SignedFichaController`
(invocáveis), que servem downloads binários em `GET /admin/alunos/{student}/ficha`
e `.../ficha-assinada`. Download de arquivo não é página Livewire: uma URL estável
abre em nova aba, sobrevive a refresh e é testável com `$this->get(...)`.

### Key directories
- `app/Livewire/` — Livewire component classes (primary home for application logic)
- `resources/views/livewire/` — Blade templates for Livewire components
- `resources/views/layouts/` — Shared layouts

`resources/views/welcome.blade.php` is an orphaned draft — no route points to it.

### Databases
- **Local dev** (`.env`): Neon.tech hosted PostgreSQL (`sa-east-1`)
- **Tests** (`.env.testing`): SQLite at `database/testing.sqlite`. O arquivo do banco
  é ignorado pelo git; crie-o com `touch database/testing.sqlite` na primeira vez.
  O `.env.testing` **é** versionado (chave descartável, sem segredo de produção).

Session, cache, and queue all use the `database` driver, so the migrations must be run before the app works.

### Autenticação
O login é por **nome de usuário**, não e-mail — `users.username` (3–30
caracteres, `[a-z0-9_.]`, único) e `Auth::attempt(['username' => ...,
'password' => ...])`, com rate limiting de 5 tentativas por usuário+IP. Há
dois papéis em `users.role`: `professor` (usa o sistema) e `admin` (quem
entrega o sistema: gere professores em `/admin/usuarios`, não vê dados de aluno
— gate `view-students` → 403 — e **nunca aparece na lista de usuários**, nem
para si mesmo). Não há tela pública de registro. `users.email` é nullable e não
é usado. `User::homeRoute()` decide onde cada papel cai após o login.

`ProfessorSeeder` e `AdminSeeder` são só bootstrap: criam o primeiro usuário
de cada papel a partir de `config/professor.php` / `config/admin.php`
(variáveis `PROFESSOR_*` / `ADMIN_*`) **apenas quando não existe ninguém
daquele papel** — removidos contam. Ele nunca apaga usuários nem reseta
senha, porque roda a cada deploy e o painel é a fonte da verdade depois do
primeiro acesso.

`User` usa `SoftDeletes`: um removido não loga (o escopo global barra
`Auth::attempt` e a leitura da sessão) e o `username` continua ocupado no índice
único até ser restaurado. Sob `middleware('auth')`: `/admin/dashboard`,
`/admin/senha` (`Admin\ChangePassword`), `/admin/usuarios` (`Admin\Users`:
criar, editar, remover, restaurar; não remove a si mesmo nem o último ativo), a
ficha em PDF e a ficha assinada.

### Ficha de cadastro (SMER)
`GET /admin/alunos/{student}/ficha` gera com dompdf a Ficha de Cadastro de Atleta
no formato da Secretaria Municipal de Esportes e Recreação de Prudentópolis, a
partir de `resources/views/pdf/ficha-atleta.blade.php`. Nada é gravado em disco.
O cabeçalho usa `public/img/prudentopolis-smer.png` se o arquivo existir, e cai
num cabeçalho em HTML caso contrário.

A ficha assinada volta pelo upload no modal do dashboard, é guardada no disco
`local` (privada — tem RG e endereço de menor) e baixada pela rota autenticada
`admin.students.ficha-assinada`.

### Deploy
Contêiner Docker (nginx + php-fpm) no Render; ver [docs/DEPLOY.md](docs/DEPLOY.md).
O `docker/start.sh` roda as migrations, o `ProfessorSeeder` e os caches de
config/rotas/views antes de subir o servidor.

As fichas assinadas usam `config('fichas.disk')` (env `FICHAS_DISK`): `local` em
desenvolvimento e nos testes, `r2` em produção — o disco do Render é descartado
a cada implantação. O disco `r2` está em `config/filesystems.php`.

`StudentSeeder` (três alunos, um em cada situação de vencimento) e `DemoSeeder`
(`prof_jiujitsu`/`prof_muaythai`/`prof_taekwondo`/`prof_boxe` + 150 alunos com
`matricula_em` espalhado por 15 meses) são só para desenvolvimento e se recusam
a rodar quando `APP_ENV=production`. O `DatabaseSeeder` usa `WithoutModelEvents`,
então seeders precisam gravar `matricula_em` explicitamente — o hook `creating`
do `Student` não roda ali.

### Vencimento da matrícula
A matrícula vale um ano a partir de `students.matricula_em` (preenchido com
`today()` no `creating`; a migration copiou `created_at` para quem já existia).
`Student` expõe `vence_em`, `diasParaVencer()`, `situacaoMatricula()` (`ok` |
`vencendo` ≤ 30 dias | `vencida`), `textoVencimento()` ("faltam 9 meses",
"vencida há 3 dias") e `renovarMatricula()` (recomeça de hoje). O dashboard
ordena a lista pelo vencimento, tem os filtros `termoFilter` (status do termo)
e `onlyAttention`, o botão Renovar
no modal (só para ativos: `findOrFail` sem `withTrashed`) e o aviso
`avisoAberto`/`dismissAviso()`, que abre uma vez por sessão (flag
`Dashboard::SESSION_AVISO`) listando vencidas e a vencer.

No Cypress, `cy.fecharAviso()` (`cypress/support/e2e.js`) fecha esse aviso
após o login, porque ele cobre a tela e bloqueia cliques.

### Cancelamento de matrícula
`Student` e `Responsible` usam `SoftDeletes`. "Cancelar matrícula" no modal do
dashboard faz soft delete só do aluno — responsável e ficha assinada ficam —
e "Reativar" restaura. A lista esconde cancelados até marcar "Mostrar
matrículas canceladas"; as rotas de ficha respondem 404 para cancelados (route
binding sem `withTrashed`). No formulário público, o CPF de um aluno cancelado
recebe a mensagem "Esta matrícula foi cancelada…": reativar é ação do
professor, não uma matrícula nova.

### Laravel 13 bootstrap style
No `Kernel.php`, `Handler.php`, or Kernel classes. Middleware and exception handling are configured inline in `bootstrap/app.php` using the fluent `Application::configure()` API.

### Vite / HMR
`vite.config.js` auto-detects GitHub Codespaces (via `CODESPACE_NAME`) and switches HMR to WSS on port 443. Binds to `0.0.0.0` for Docker/WSL compatibility.

### Tailwind
`tailwind.config.js` includes `app/Livewire/**/*.php` in content paths — Livewire component PHP files can contain Tailwind class strings that must be scanned.
