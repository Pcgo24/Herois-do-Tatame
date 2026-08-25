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
O login é por **CPF**, não e-mail — `users.cpf` (11 dígitos, único) e
`Auth::attempt(['cpf' => ..., 'password' => ...])`, com rate limiting de 5
tentativas por CPF+IP. Existe um único tipo de usuário, o professor; não há
coluna `role` nem tela de registro.

O usuário é criado por `ProfessorSeeder`, que lê `config/professor.php` (e este,
as variáveis `PROFESSOR_*` do `.env`). O seeder apaga qualquer usuário com CPF
diferente antes de criar o novo — existe exatamente um professor, por projeto,
e trocar o CPF precisa renomeá-lo, não somar um segundo. Sob `middleware('auth')`:
`/admin/dashboard`, a ficha em PDF e a ficha assinada.

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

`StudentSeeder` cria três alunos de demonstração e se recusa a rodar quando
`APP_ENV=production`.

### Laravel 13 bootstrap style
No `Kernel.php`, `Handler.php`, or Kernel classes. Middleware and exception handling are configured inline in `bootstrap/app.php` using the fluent `Application::configure()` API.

### Vite / HMR
`vite.config.js` auto-detects GitHub Codespaces (via `CODESPACE_NAME`) and switches HMR to WSS on port 443. Binds to `0.0.0.0` for Docker/WSL compatibility.

### Tailwind
`tailwind.config.js` includes `app/Livewire/**/*.php` in content paths — Livewire component PHP files can contain Tailwind class strings that must be scanned.
