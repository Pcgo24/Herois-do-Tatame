# Login do professor e ficha SMER em PDF — Plano de Implementação

> **Para executores agênticos:** SUB-SKILL OBRIGATÓRIA: use superpowers:subagent-driven-development (recomendado) ou superpowers:executing-plans para implementar tarefa a tarefa. Os passos usam checkbox (`- [ ]`) para acompanhamento.

**Goal:** Autenticar o professor por CPF, ampliar o cadastro de matrícula com os campos da ficha oficial da SMER e gerar essa ficha em PDF pronta para assinatura.

**Architecture:** Uma migration acrescenta as colunas da ficha a `responsibles` e `students` (todas `nullable` no banco, obrigatoriedade imposta na validação). O login é um componente Livewire que autentica por `cpf` via `Auth::attempt`, com rate limiting. A ficha é gerada sob demanda por um controller invocável que renderiza uma Blade A4 no dompdf — nada é gravado em disco.

**Tech Stack:** Laravel 13, Livewire 4, Tailwind CSS, Alpine.js, PostgreSQL (Neon) em desenvolvimento, SQLite nos testes, `barryvdh/laravel-dompdf`, PHPUnit 12, Cypress.

**Spec:** [docs/superpowers/specs/2026-08-24-login-e-ficha-pdf-design.md](../specs/2026-08-24-login-e-ficha-pdf-design.md)

## Global Constraints

- **Idioma:** toda a interface e todas as mensagens de validação em português do Brasil, com acentuação correta. Nomes de código (classes, métodos, colunas, propriedades) em inglês, seguindo o que já existe no repositório.
- **Commits são manuais.** O Paulo executa os commits. Os passos de commit deste plano são **sugestões de mensagem e de arquivos** — não execute `git commit`, apenas apresente o comando ao final da tarefa.
- **Branch:** `Paulo`.
- **Todo comando roda via Sail:** `./vendor/bin/sail artisan ...`, `./vendor/bin/sail composer ...`.
- **Suíte de testes:** `./vendor/bin/sail artisan test --testsuite Feature --filter <NomeDoTeste>`.
- **`data-cy` obrigatório** em todo input e toda mensagem de erro novos no formulário — o Cypress depende desses seletores.
- **Colunas novas entram `nullable` no banco.** A tabela já tem linhas em produção; `NOT NULL` quebraria a migration. A obrigatoriedade vive na validação.
- **Padrão de máscara:** inputs mascarados usam Alpine com `x-effect` para exibir formatado e `$wire.set(...)` para gravar apenas dígitos, exatamente como já ocorre em `resources/views/livewire/enrollment-form.blade.php`.
- **Lint ao final de cada tarefa:** `./vendor/bin/sail exec laravel.test vendor/bin/pint`.
- **Modalidades válidas:** `Jiu Jitsu`, `Muay Thai`, `Taekwondo`, `Boxe`.
- **Faixa etária do aluno:** 8 a 17 anos. **Idade mínima do responsável:** 18 anos.

---

### Task 0: Corrigir a infraestrutura de testes

O CLAUDE.md afirma que os testes rodam em SQLite via `.env.testing`. Esse arquivo não existe. Na prática a suíte conecta no **Neon**, num banco `testing` do mesmo endpoint de produção — cada teste paga latência de rede para `sa-east-1` (o primeiro leva ~8 s) e o `RefreshDatabase` roda contra a infraestrutura real. Além disso, `phpunit.xml` aponta para `tests/Unit`, que não existe, e por isso `sail artisan test` sem `--testsuite` aborta.

Este plano acrescenta cerca de 40 testes. Corrigir isso primeiro é o que torna as tarefas seguintes rápidas e seguras.

**Files:**
- Create: `.env.testing`
- Create: `tests/Unit/.gitkeep`
- Create: `database/testing.sqlite` (arquivo vazio)
- Modify: `.gitignore`

**Interfaces:**
- Consumes: nada.
- Produces: suíte de testes rodando em SQLite local; `./vendor/bin/sail artisan test` funciona sem `--testsuite`.

- [ ] **Step 1: Confirmar o problema**

Run: `./vendor/bin/sail artisan test --testsuite Feature --filter test_dashboard_renders_for_authenticated_user`

Esperado: PASSA, porém o teste leva mais de 5 segundos — evidência da ida ao Neon.

- [ ] **Step 2: Criar o banco SQLite de testes e o diretório Unit**

```bash
touch database/testing.sqlite
mkdir -p tests/Unit && touch tests/Unit/.gitkeep
```

- [ ] **Step 3: Criar `.env.testing`**

```
APP_ENV=testing
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/testing.sqlite
DB_FOREIGN_KEYS=true

SESSION_DRIVER=array
CACHE_STORE=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
BCRYPT_ROUNDS=4
```

O caminho é absoluto dentro do container porque o Sail monta o projeto em `/var/www/html`.

- [ ] **Step 4: Copiar a APP_KEY do `.env` para o `.env.testing`**

```bash
grep '^APP_KEY=' .env
```

Cole o valor obtido na linha `APP_KEY=` do `.env.testing`. Sem chave, o Laravel lança `MissingAppKeyException` ao encriptar sessão.

- [ ] **Step 5: Remover o override de banco do `phpunit.xml`**

O `.env.testing` agora manda no banco. As duas linhas abaixo, em `phpunit.xml`, forçariam `DB_DATABASE=testing` e anulariam o caminho do SQLite:

```xml
<env name="DB_DATABASE" value="testing"/>
<env name="DB_URL" value=""/>
```

Apague as duas.

- [ ] **Step 6: Ignorar o banco de testes no git**

Acrescente ao final de `.gitignore`:

```
/database/testing.sqlite
/testing
```

A segunda linha é o arquivo SQLite órfão de 124 KB na raiz do repositório, resíduo da configuração antiga.

- [ ] **Step 7: Rodar a suíte inteira**

Run: `./vendor/bin/sail artisan test`

Esperado: todos os testes passam e a duração total cai para poucos segundos. Se algum teste falhar por diferença entre PostgreSQL e SQLite, corrija o teste — não volte para o Neon.

- [ ] **Step 8: Apagar o arquivo SQLite órfão da raiz**

```bash
rm -f testing
```

- [ ] **Step 9: Commit (sugestão para o Paulo)**

```bash
git add .env.testing .gitignore phpunit.xml tests/Unit/.gitkeep && git commit -m "test: rodar suite em sqlite local em vez do neon"
```

---

### Task 1: Colunas da ficha em `responsibles` e `students`

**Files:**
- Create: `database/migrations/2026_08_24_000000_add_ficha_fields_to_responsibles_and_students.php`
- Modify: `app/Models/Responsible.php`
- Modify: `app/Models/Student.php`
- Modify: `database/factories/ResponsibleFactory.php`
- Modify: `database/factories/StudentFactory.php`
- Test: `tests/Feature/FichaFieldsTest.php`

**Interfaces:**
- Consumes: nada.
- Produces:
  - `Responsible` com `rg`, `neighborhood`, `home_phone` em `$fillable`.
  - `Student` com `school`, `grade`, `father_name`, `mother_name`, `no_father`, `no_mother`, `phone`, `email` em `$fillable`; `no_father` e `no_mother` convertidos para `bool`.
  - `ResponsibleFactory` e `StudentFactory` preenchendo todos os campos novos.

- [ ] **Step 1: Escrever o teste que falha**

Crie `tests/Feature/FichaFieldsTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Responsible;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FichaFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_responsible_persists_ficha_fields(): void
    {
        $responsible = Responsible::factory()->create([
            'rg' => '123456789',
            'neighborhood' => 'Centro',
            'home_phone' => '4232241234',
        ]);

        $this->assertSame('123456789', $responsible->fresh()->rg);
        $this->assertSame('Centro', $responsible->fresh()->neighborhood);
        $this->assertSame('4232241234', $responsible->fresh()->home_phone);
    }

    public function test_student_persists_ficha_fields(): void
    {
        $student = Student::factory()->create([
            'school' => 'Colégio Estadual Prudentópolis',
            'grade' => '7º ano',
            'father_name' => 'José da Silva',
            'mother_name' => 'Maria da Silva',
            'phone' => '42999998888',
            'email' => 'joao@example.com',
        ]);

        $fresh = $student->fresh();

        $this->assertSame('Colégio Estadual Prudentópolis', $fresh->school);
        $this->assertSame('7º ano', $fresh->grade);
        $this->assertSame('José da Silva', $fresh->father_name);
        $this->assertSame('Maria da Silva', $fresh->mother_name);
        $this->assertSame('42999998888', $fresh->phone);
        $this->assertSame('joao@example.com', $fresh->email);
    }

    public function test_student_filiation_flags_are_booleans(): void
    {
        $student = Student::factory()->create([
            'father_name' => null,
            'no_father' => true,
        ]);

        $fresh = $student->fresh();

        $this->assertTrue($fresh->no_father);
        $this->assertFalse($fresh->no_mother);
        $this->assertNull($fresh->father_name);
    }

    public function test_home_phone_is_optional(): void
    {
        $responsible = Responsible::factory()->create(['home_phone' => null]);

        $this->assertNull($responsible->fresh()->home_phone);
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

Run: `./vendor/bin/sail artisan test --filter FichaFieldsTest`
Esperado: FALHA com erro de coluna inexistente (`no such column: rg`).

- [ ] **Step 3: Criar a migration**

Run: `./vendor/bin/sail artisan make:migration add_ficha_fields_to_responsibles_and_students`

Renomeie o arquivo gerado para `2026_08_24_000000_add_ficha_fields_to_responsibles_and_students.php` e substitua o conteúdo:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('responsibles', function (Blueprint $table) {
            $table->string('rg', 20)->nullable()->after('cpf');
            $table->string('neighborhood', 80)->nullable()->after('address');
            $table->string('home_phone', 11)->nullable()->after('phone_number');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->string('school', 120)->nullable()->after('birth_date');
            $table->string('grade', 30)->nullable()->after('school');
            $table->string('father_name', 80)->nullable()->after('grade');
            $table->string('mother_name', 80)->nullable()->after('father_name');
            $table->boolean('no_father')->default(false)->after('mother_name');
            $table->boolean('no_mother')->default(false)->after('no_father');
            $table->string('phone', 11)->nullable()->after('no_mother');
            $table->string('email')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('responsibles', function (Blueprint $table) {
            $table->dropColumn(['rg', 'neighborhood', 'home_phone']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'school', 'grade', 'father_name', 'mother_name',
                'no_father', 'no_mother', 'phone', 'email',
            ]);
        });
    }
};
```

Todas as colunas são `nullable` de propósito — ver Global Constraints.

- [ ] **Step 4: Declarar as colunas nos models**

Em `app/Models/Responsible.php`, o `$fillable` passa a ser:

```php
    protected $fillable = [
        'name',
        'phone_number',
        'home_phone',
        'cpf',
        'rg',
        'email',
        'birth_date',
        'address',
        'neighborhood',
    ];
```

Em `app/Models/Student.php`, o `$fillable` passa a ser:

```php
    protected $fillable = [
        'responsible_id',
        'name',
        'cpf',
        'rg',
        'birth_date',
        'school',
        'grade',
        'father_name',
        'mother_name',
        'no_father',
        'no_mother',
        'phone',
        'email',
        'modalidade',
        'termo_status',
        'termo_arquivo',
    ];
```

e o `casts()` do mesmo arquivo:

```php
    protected function casts(): array
    {
        return [
            'id' => 'string',
            'responsible_id' => 'string',
            'birth_date' => 'date',
            'no_father' => 'boolean',
            'no_mother' => 'boolean',
        ];
    }
```

- [ ] **Step 5: Atualizar as factories**

Em `database/factories/ResponsibleFactory.php`, o `definition()` retorna:

```php
        return [
            'name'         => $this->faker->name(),
            'phone_number' => $this->faker->numerify('###########'),
            'home_phone'   => $this->faker->optional(0.5)->numerify('##########'),
            'cpf'          => $this->faker->unique()->numerify('###########'),
            'rg'           => $this->faker->numerify('#########'),
            'email'        => $this->faker->unique()->safeEmail(),
            'birth_date'   => $this->faker->dateTimeBetween('-60 years', '-19 years')->format('Y-m-d'),
            'address'      => substr($this->faker->streetAddress().', '.$this->faker->city(), 0, 150),
            'neighborhood' => substr($this->faker->citySuffix(), 0, 80),
        ];
```

Em `database/factories/StudentFactory.php`, o `definition()` retorna:

```php
        return [
            'responsible_id' => Responsible::factory(),
            'name'           => $this->faker->name(),
            'cpf'            => $this->faker->unique()->numerify('###########'),
            'rg'             => $this->faker->numerify('#########'),
            'birth_date'     => $this->faker->dateTimeBetween('-16 years', '-9 years')->format('Y-m-d'),
            'school'         => 'Colégio Estadual de Prudentópolis',
            'grade'          => $this->faker->numberBetween(1, 9).'º ano',
            'father_name'    => $this->faker->name('male'),
            'mother_name'    => $this->faker->name('female'),
            'no_father'      => false,
            'no_mother'      => false,
            'phone'          => $this->faker->optional(0.4)->numerify('###########'),
            'email'          => $this->faker->optional(0.4)->safeEmail(),
            'modalidade'     => $this->faker->randomElement(['Jiu Jitsu', 'Muay Thai', 'Taekwondo', 'Boxe']),
            'termo_status'   => 'pendente',
        ];
```

`rg` deixa de ser `optional()` porque a ficha passa a exigi-lo.

- [ ] **Step 6: Rodar as migrations e o teste**

Run: `./vendor/bin/sail artisan test --filter FichaFieldsTest`
Esperado: 4 testes PASSAM.

- [ ] **Step 7: Rodar a suíte inteira, para garantir que nada quebrou**

Run: `./vendor/bin/sail artisan test`
Esperado: tudo passa.

- [ ] **Step 8: Aplicar a migration no banco de desenvolvimento**

Run: `./vendor/bin/sail artisan migrate`

- [ ] **Step 9: Lint e commit (sugestão para o Paulo)**

```bash
./vendor/bin/sail exec laravel.test vendor/bin/pint
```

```bash
git add database/migrations app/Models database/factories tests/Feature/FichaFieldsTest.php && git commit -m "feat: colunas da ficha smer em responsibles e students"
```

---

### Task 2: CPF em `users` e seeder do professor

**Files:**
- Create: `database/migrations/2026_08_24_000001_add_cpf_to_users_table.php`
- Create: `database/seeders/ProfessorSeeder.php`
- Modify: `app/Models/User.php`
- Modify: `database/factories/UserFactory.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `.env.example`
- Test: `tests/Feature/ProfessorSeederTest.php`

**Interfaces:**
- Consumes: nada.
- Produces:
  - `users.cpf` (string 11, único, nullable).
  - `User` aceitando `cpf` em massa.
  - `UserFactory` gerando `cpf` único.
  - `ProfessorSeeder`, idempotente, lendo `PROFESSOR_CPF` / `PROFESSOR_PASSWORD` / `PROFESSOR_NAME` / `PROFESSOR_EMAIL`.

- [ ] **Step 1: Escrever o teste que falha**

Crie `tests/Feature/ProfessorSeederTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ProfessorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfessorSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_professor_with_default_cpf(): void
    {
        $this->seed(ProfessorSeeder::class);

        $professor = User::where('cpf', '12345678909')->first();

        $this->assertNotNull($professor);
        $this->assertTrue(Hash::check('heroisdotatame', $professor->password));
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(ProfessorSeeder::class);
        $this->seed(ProfessorSeeder::class);

        $this->assertSame(1, User::where('cpf', '12345678909')->count());
    }

    public function test_seeder_respects_environment_variables(): void
    {
        config()->set('professor.cpf', '98765432100');
        config()->set('professor.password', 'senha-secreta');

        $this->seed(ProfessorSeeder::class);

        $professor = User::where('cpf', '98765432100')->first();

        $this->assertNotNull($professor);
        $this->assertTrue(Hash::check('senha-secreta', $professor->password));
    }

    public function test_user_factory_generates_cpf(): void
    {
        $user = User::factory()->create();

        $this->assertMatchesRegularExpression('/^\d{11}$/', $user->cpf);
    }
}
```

O terceiro teste usa `config()` em vez de `env()` porque `env()` não é lido depois do boot quando a configuração está cacheada. Por isso o seeder lê de `config('professor.*')`, e é a configuração que lê o `.env`.

- [ ] **Step 2: Rodar o teste e confirmar que falha**

Run: `./vendor/bin/sail artisan test --filter ProfessorSeederTest`
Esperado: FALHA com `Class "Database\Seeders\ProfessorSeeder" not found`.

- [ ] **Step 3: Criar a migration da coluna `cpf`**

Run: `./vendor/bin/sail artisan make:migration add_cpf_to_users_table`

Renomeie para `2026_08_24_000001_add_cpf_to_users_table.php` e substitua o conteúdo:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cpf', 11)->nullable()->unique()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('cpf');
        });
    }
};
```

`nullable` porque a tabela pode já ter usuários sem CPF; a unicidade continua valendo, e o PostgreSQL permite múltiplos `NULL` num índice único.

- [ ] **Step 4: Criar o arquivo de configuração do professor**

Crie `config/professor.php`:

```php
<?php

return [
    'cpf' => env('PROFESSOR_CPF', '12345678909'),
    'password' => env('PROFESSOR_PASSWORD', 'heroisdotatame'),
    'name' => env('PROFESSOR_NAME', 'Professor'),
    'email' => env('PROFESSOR_EMAIL', 'professor@heroisdotatame.local'),
];
```

- [ ] **Step 5: Permitir `cpf` em massa no model**

Em `app/Models/User.php`, o atributo de fillable passa a ser:

```php
#[Fillable(['name', 'cpf', 'email', 'password'])]
```

- [ ] **Step 6: Gerar CPF na factory**

Em `database/factories/UserFactory.php`, acrescente a chave `cpf` ao array retornado por `definition()`, logo depois de `'name'`:

```php
            'cpf' => fake()->unique()->numerify('###########'),
```

- [ ] **Step 7: Escrever o seeder**

Crie `database/seeders/ProfessorSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProfessorSeeder extends Seeder
{
    public function run(): void
    {
        $cpf = preg_replace('/\D/', '', (string) config('professor.cpf'));

        User::updateOrCreate(
            ['cpf' => $cpf],
            [
                'name' => config('professor.name'),
                'email' => config('professor.email'),
                'password' => Hash::make(config('professor.password')),
                'email_verified_at' => now(),
            ],
        );

        $this->command?->info("Professor disponível para login com o CPF {$cpf}.");
    }
}
```

- [ ] **Step 8: Registrar o seeder**

Em `database/seeders/DatabaseSeeder.php`, o corpo de `run()` passa a ser:

```php
    public function run(): void
    {
        $this->call(ProfessorSeeder::class);
    }
```

O `User::factory()->create(['name' => 'Test User', ...])` que estava ali sai — era resíduo do esqueleto do Laravel e criaria um usuário sem CPF, que não consegue logar.

- [ ] **Step 9: Documentar as variáveis no `.env.example`**

Acrescente ao final de `.env.example`:

```
# Credenciais do professor criadas por ProfessorSeeder.
# TROQUE A SENHA ANTES DE QUALQUER USO REAL — o valor padrão serve só para desenvolvimento.
PROFESSOR_CPF=12345678909
PROFESSOR_PASSWORD=heroisdotatame
PROFESSOR_NAME=Professor
PROFESSOR_EMAIL=professor@heroisdotatame.local
```

- [ ] **Step 10: Rodar o teste**

Run: `./vendor/bin/sail artisan test --filter ProfessorSeederTest`
Esperado: 4 testes PASSAM.

- [ ] **Step 11: Aplicar no banco de desenvolvimento e criar o professor**

```bash
./vendor/bin/sail artisan migrate && ./vendor/bin/sail artisan db:seed --class=ProfessorSeeder
```

Esperado: mensagem `Professor disponível para login com o CPF 12345678909.`

- [ ] **Step 12: Lint e commit (sugestão para o Paulo)**

```bash
./vendor/bin/sail exec laravel.test vendor/bin/pint
```

```bash
git add database config app/Models/User.php .env.example tests/Feature/ProfessorSeederTest.php && git commit -m "feat: cpf em users e seeder do professor"
```

---

### Task 3: Tela de login por CPF

**Files:**
- Create: `app/Livewire/Auth/Login.php`
- Create: `resources/views/livewire/auth/login.blade.php`
- Create: `resources/views/layouts/auth.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Auth/LoginTest.php`

**Interfaces:**
- Consumes: `users.cpf` e `UserFactory` com `cpf` (Task 2).
- Produces:
  - `App\Livewire\Auth\Login` com propriedades públicas `cpf`, `password`, `remember` e o método `login()`.
  - Rota nomeada `login` em `GET /login`.
  - Layout `layouts.auth`.

- [ ] **Step 1: Escrever o teste que falha**

Crie `tests/Feature/Auth/LoginTest.php`:

```php
<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function professor(): User
    {
        return User::factory()->create([
            'cpf' => '12345678909',
            'password' => Hash::make('senha-correta'),
        ]);
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Entrar');
    }

    public function test_professor_logs_in_with_valid_credentials(): void
    {
        $professor = $this->professor();

        Livewire::test(Login::class)
            ->set('cpf', '12345678909')
            ->set('password', 'senha-correta')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($professor);
    }

    public function test_wrong_password_does_not_authenticate(): void
    {
        $this->professor();

        Livewire::test(Login::class)
            ->set('cpf', '12345678909')
            ->set('password', 'senha-errada')
            ->call('login')
            ->assertHasErrors('cpf');

        $this->assertGuest();
    }

    public function test_unknown_cpf_shows_the_same_generic_message(): void
    {
        $this->professor();

        Livewire::test(Login::class)
            ->set('cpf', '99999999999')
            ->set('password', 'senha-correta')
            ->call('login')
            ->assertHasErrors('cpf');

        $this->assertGuest();
    }

    public function test_cpf_must_have_eleven_digits(): void
    {
        Livewire::test(Login::class)
            ->set('cpf', '123')
            ->set('password', 'senha-correta')
            ->call('login')
            ->assertHasErrors(['cpf' => 'regex']);

        $this->assertGuest();
    }

    public function test_sixth_attempt_is_rate_limited(): void
    {
        $this->professor();

        $component = Livewire::test(Login::class)
            ->set('cpf', '12345678909')
            ->set('password', 'senha-errada');

        for ($i = 0; $i < 5; $i++) {
            $component->call('login');
        }

        $component->set('password', 'senha-correta')->call('login');

        $this->assertGuest();
        $this->assertStringContainsString(
            'Muitas tentativas',
            $component->errors()->first('cpf'),
        );
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $this->actingAs($this->professor());

        $this->get('/login')->assertRedirect(route('admin.dashboard'));
    }

    public function test_logout_ends_the_session(): void
    {
        $this->actingAs($this->professor());

        $this->post('/logout')->assertRedirect('/');

        $this->assertGuest();
    }
}
```

- [ ] **Step 2: Rodar o teste e confirmar que falha**

Run: `./vendor/bin/sail artisan test --filter LoginTest`
Esperado: FALHA com `Class "App\Livewire\Auth\Login" not found`.

- [ ] **Step 3: Escrever o componente**

Crie `app/Livewire/Auth/Login.php`:

```php
<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    public string $cpf = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'cpf' => ['required', 'regex:/^\d{11}$/'],
            'password' => ['required', 'string'],
        ], [
            'cpf.required' => 'Informe seu CPF.',
            'cpf.regex' => 'O CPF deve conter 11 dígitos.',
            'password.required' => 'Informe sua senha.',
        ]);

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['cpf' => $this->cpf, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey(), 60);

            Log::warning('Tentativa de login malsucedida.', [
                'cpf' => $this->cpf,
                'ip' => request()->ip(),
            ]);

            throw ValidationException::withMessages([
                'cpf' => 'CPF ou senha inválidos.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        $this->redirect(route('admin.dashboard'), navigate: false);
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'cpf' => "Muitas tentativas. Tente novamente em {$seconds} segundos.",
        ]);
    }

    private function throttleKey(): string
    {
        return 'login:'.$this->cpf.'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.auth');
    }
}
```

A mensagem é a mesma para senha errada e CPF inexistente — não revela se o CPF está cadastrado.

- [ ] **Step 4: Criar o layout de autenticação**

Crie `resources/views/layouts/auth.blade.php`:

```blade
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar — Heróis do Tatame</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important;}</style>
</head>
<body class="bg-black text-white antialiased font-sans min-h-screen flex items-center justify-center px-6 py-12">
    {{ $slot }}
</body>
</html>
```

- [ ] **Step 5: Criar a view do componente**

Crie `resources/views/livewire/auth/login.blade.php`:

```blade
<div class="w-full max-w-md">

    <div class="flex items-center justify-center gap-2 mb-8">
        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
        <a href="{{ route('home') }}" class="text-xl font-bold tracking-widest uppercase">Heróis do Tatame</a>
    </div>

    <div class="bg-neutral-950 border border-neutral-800 rounded-2xl p-8">

        <h1 class="text-2xl font-bold text-white mb-1">Área do Professor</h1>
        <p class="text-neutral-500 text-sm mb-8">Entre com seu CPF e senha para acessar os cadastros.</p>

        <form wire:submit="login" data-cy="login-form" novalidate>

            <div class="mb-5" x-data="{
                fmt(v) {
                    v = String(v||'').replace(/\D/g,'').substring(0,11);
                    return v.length>9 ? v.slice(0,3)+'.'+v.slice(3,6)+'.'+v.slice(6,9)+'-'+v.slice(9)
                         : v.length>6 ? v.slice(0,3)+'.'+v.slice(3,6)+'.'+v.slice(6)
                         : v.length>3 ? v.slice(0,3)+'.'+v.slice(3) : v;
                }
            }">
                <label class="block text-sm font-medium text-neutral-400 mb-1.5">CPF</label>
                <input
                    type="text"
                    data-cy="input-cpf"
                    maxlength="14"
                    inputmode="numeric"
                    autocomplete="username"
                    placeholder="123.456.789-01"
                    x-effect="if (document.activeElement !== $el) $el.value = fmt($wire.cpf)"
                    x-on:input="let r=$el.value.replace(/\D/g,'').substring(0,11); $el.value=fmt(r); $wire.set('cpf',r);"
                    class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                           {{ $errors->has('cpf') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                >
                @error('cpf')
                    <p class="text-red-400 text-sm mt-1.5" data-cy="error-cpf">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-5">
                <label class="block text-sm font-medium text-neutral-400 mb-1.5">Senha</label>
                <input
                    type="password"
                    wire:model="password"
                    data-cy="input-password"
                    autocomplete="current-password"
                    class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                           {{ $errors->has('password') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                >
                @error('password')
                    <p class="text-red-400 text-sm mt-1.5" data-cy="error-password">{{ $message }}</p>
                @enderror
            </div>

            <label class="flex items-center gap-2 mb-8 cursor-pointer">
                <input type="checkbox" wire:model="remember" data-cy="input-remember" class="w-4 h-4 accent-white cursor-pointer">
                <span class="text-sm text-neutral-400">Manter conectado neste dispositivo</span>
            </label>

            <button
                type="submit"
                data-cy="login-btn"
                wire:loading.attr="disabled"
                class="bg-white text-black hover:bg-neutral-200 font-bold px-10 py-3 rounded-lg transition w-full disabled:opacity-60 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove>Entrar</span>
                <span wire:loading class="flex items-center justify-center gap-2">
                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Entrando...
                </span>
            </button>

        </form>
    </div>

    <p class="text-center mt-6">
        <a href="{{ route('home') }}" class="text-neutral-500 hover:text-neutral-300 text-sm transition">← Voltar ao início</a>
    </p>
</div>
```

- [ ] **Step 6: Trocar a rota de login**

Em `routes/web.php`, substitua a linha

```php
Route::get('/login', fn () => redirect('/'))->name('login');
```

por

```php
Route::get('/login', Login::class)->middleware('guest')->name('login');
```

e acrescente o import no topo do arquivo:

```php
use App\Livewire\Auth\Login;
```

O middleware `guest` redireciona quem já está logado. O destino padrão dele é `/dashboard`; aponte-o para o dashboard admin acrescentando ao `boot()` de `app/Providers/AppServiceProvider.php`:

```php
        \Illuminate\Auth\Middleware\RedirectIfAuthenticated::redirectUsing(
            fn () => route('admin.dashboard')
        );
```

- [ ] **Step 7: Rodar o teste**

Run: `./vendor/bin/sail artisan test --filter LoginTest`
Esperado: 8 testes PASSAM.

- [ ] **Step 8: Conferir no navegador**

Suba o Vite com `./vendor/bin/sail npm run dev`, acesse `/login` e entre com `123.456.789-01` e `heroisdotatame`. Esperado: cair no dashboard.

- [ ] **Step 9: Lint e commit (sugestão para o Paulo)**

```bash
./vendor/bin/sail exec laravel.test vendor/bin/pint
```

```bash
git add app/Livewire/Auth app/Providers/AppServiceProvider.php resources/views/livewire/auth resources/views/layouts/auth.blade.php routes/web.php tests/Feature/Auth && git commit -m "feat: login do professor por cpf com rate limiting"
```

---

### Task 4: Proteger o dashboard administrativo

**Files:**
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `tests/Feature/Admin/DashboardTest.php:98-103`

**Interfaces:**
- Consumes: a rota `login` (Task 3).
- Produces: `/admin/dashboard` acessível apenas com sessão autenticada.

- [ ] **Step 1: Trocar o teste que afirma o contrário**

Em `tests/Feature/Admin/DashboardTest.php`, o método `test_admin_dashboard_is_publicly_accessible` (com o `TODO` acima dele) sai inteiro e dá lugar a estes dois:

```php
    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
    }

    public function test_authenticated_user_reaches_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/dashboard')->assertOk();
    }
```

- [ ] **Step 2: Rodar e confirmar a falha**

Run: `./vendor/bin/sail artisan test --filter DashboardTest`
Esperado: `test_guest_is_redirected_to_login` FALHA — recebe 200 em vez de redirect.

- [ ] **Step 3: Mover a rota para o grupo autenticado**

Em `routes/web.php`, apague estas duas linhas:

```php
// TODO: proteger com middleware 'auth' quando o login for implementado.
Route::get('/admin/dashboard', Dashboard::class)->name('admin.dashboard');
```

e declare a rota dentro do grupo `auth` que já existe:

```php
Route::middleware('auth')->group(function () {
    Route::get('/admin/dashboard', Dashboard::class)->name('admin.dashboard');

    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
});
```

- [ ] **Step 4: Dar ao professor um caminho até o login**

Em `resources/views/layouts/app.blade.php`, dentro da `<nav class="hidden md:flex ...">`, acrescente antes do link "Matricule-se":

```blade
                <a href="{{ route('login') }}" class="hover:text-white transition">Área do Professor</a>
```

E no menu mobile, antes do "Matricule-se", acrescente:

```blade
                <a href="{{ route('login') }}" @click="menuAberto = false" class="text-gray-400 hover:text-white text-lg font-medium border-b border-neutral-800 pb-3 transition">Área do Professor</a>
```

- [ ] **Step 5: Rodar o teste**

Run: `./vendor/bin/sail artisan test --filter DashboardTest`
Esperado: todos PASSAM.

- [ ] **Step 6: Rodar a suíte inteira**

Run: `./vendor/bin/sail artisan test`
Esperado: tudo passa.

- [ ] **Step 7: Lint e commit (sugestão para o Paulo)**

```bash
./vendor/bin/sail exec laravel.test vendor/bin/pint
```

```bash
git add routes/web.php resources/views/layouts/app.blade.php tests/Feature/Admin/DashboardTest.php && git commit -m "feat: exigir autenticacao no dashboard admin"
```

---

### Task 5: Validação dos campos da ficha e filiação condicional

**Files:**
- Modify: `app/Http/Requests/StoreEnrollmentRequest.php`
- Modify: `app/Livewire/EnrollmentForm.php`
- Test: `tests/Feature/EnrollmentTest.php`

**Interfaces:**
- Consumes: as colunas e os models da Task 1.
- Produces:
  - `StoreEnrollmentRequest::enrollmentRules(array $state = []): array` — passa a aceitar o estado do componente para decidir se a filiação é obrigatória.
  - `EnrollmentForm` com as propriedades `responsible_rg`, `responsible_neighborhood`, `responsible_home_phone`, `student_school`, `student_grade`, `student_father_name`, `student_no_father`, `student_mother_name`, `student_no_mother`, `student_phone`, `student_email`.

- [ ] **Step 1: Atualizar os helpers do teste e escrever os testes que falham**

Em `tests/Feature/EnrollmentTest.php`, substitua `validPayload()` e `fillForm()` por:

```php
    private function validPayload(): array
    {
        return [
            'responsible_name' => 'Maria da Silva',
            'responsible_phone_number' => '11999999999',
            'responsible_home_phone' => '4232241234',
            'responsible_cpf' => '12345678901',
            'responsible_rg' => '123456789',
            'responsible_email' => 'maria@email.com',
            'responsible_birth_date' => '1990-01-15',
            'responsible_address' => 'Rua das Flores, 123',
            'responsible_neighborhood' => 'Centro',
            'student_name' => 'João da Silva',
            'student_cpf' => '98765432100',
            'student_rg' => '987654321',
            'student_birth_date' => '2015-06-10',
            'student_school' => 'Colégio Estadual de Prudentópolis',
            'student_grade' => '5º ano',
            'student_father_name' => 'José da Silva',
            'student_no_father' => false,
            'student_mother_name' => 'Maria da Silva',
            'student_no_mother' => false,
            'student_phone' => '42999998888',
            'student_email' => 'joao@email.com',
            'student_modalidade' => 'Jiu Jitsu',
            'lgpd_consent' => true,
        ];
    }

    private function fillForm(array $overrides = []): mixed
    {
        $data = array_merge($this->validPayload(), $overrides);

        $component = Livewire::test(EnrollmentForm::class);

        foreach ($data as $property => $value) {
            $component->set($property, $value);
        }

        return $component;
    }
```

O laço substitui a sequência de `->set()` encadeados: são 23 campos agora, e um laço não esquece nenhum.

Acrescente, ao final da mesma classe, os testes dos campos novos:

```php
    public function test_submission_persists_ficha_fields(): void
    {
        $this->fillForm()->call('submit')->assertHasNoErrors();

        $responsible = Responsible::first();
        $student = Student::first();

        $this->assertSame('123456789', $responsible->rg);
        $this->assertSame('Centro', $responsible->neighborhood);
        $this->assertSame('4232241234', $responsible->home_phone);
        $this->assertSame('Colégio Estadual de Prudentópolis', $student->school);
        $this->assertSame('5º ano', $student->grade);
        $this->assertSame('José da Silva', $student->father_name);
        $this->assertSame('Maria da Silva', $student->mother_name);
        $this->assertSame('42999998888', $student->phone);
        $this->assertSame('joao@email.com', $student->email);
    }

    public static function requiredFichaFieldProvider(): array
    {
        return [
            'RG do responsável' => ['responsible_rg'],
            'bairro' => ['responsible_neighborhood'],
            'RG do aluno' => ['student_rg'],
            'escola' => ['student_school'],
            'série' => ['student_grade'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('requiredFichaFieldProvider')]
    public function test_required_ficha_field_cannot_be_empty(string $field): void
    {
        $this->fillForm([$field => ''])
            ->call('submit')
            ->assertHasErrors([$field => 'required']);

        $this->assertDatabaseCount('students', 0);
    }

    public function test_home_phone_and_student_contact_are_optional(): void
    {
        $this->fillForm([
            'responsible_home_phone' => '',
            'student_phone' => '',
            'student_email' => '',
        ])->call('submit')->assertHasNoErrors();

        $this->assertNull(Responsible::first()->home_phone);
        $this->assertNull(Student::first()->phone);
        $this->assertNull(Student::first()->email);
    }

    public function test_missing_father_checkbox_replaces_the_father_name(): void
    {
        $this->fillForm([
            'student_father_name' => '',
            'student_no_father' => true,
        ])->call('submit')->assertHasNoErrors();

        $student = Student::first();

        $this->assertNull($student->father_name);
        $this->assertTrue($student->no_father);
    }

    public function test_missing_mother_checkbox_replaces_the_mother_name(): void
    {
        $this->fillForm([
            'student_mother_name' => '',
            'student_no_mother' => true,
        ])->call('submit')->assertHasNoErrors();

        $student = Student::first();

        $this->assertNull($student->mother_name);
        $this->assertTrue($student->no_mother);
    }

    public function test_father_name_is_required_without_the_checkbox(): void
    {
        $this->fillForm(['student_father_name' => ''])
            ->call('submit')
            ->assertHasErrors('student_father_name');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_at_least_one_filiation_is_required(): void
    {
        $this->fillForm([
            'student_father_name' => '',
            'student_no_father' => true,
            'student_mother_name' => '',
            'student_no_mother' => true,
        ])->call('submit')->assertHasErrors('student_no_father');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_student_email_must_be_valid_when_filled(): void
    {
        $this->fillForm(['student_email' => 'nao-e-email'])
            ->call('submit')
            ->assertHasErrors(['student_email' => 'email']);
    }
```

- [ ] **Step 2: Rodar e confirmar a falha**

Run: `./vendor/bin/sail artisan test --filter EnrollmentTest`
Esperado: FALHA — `Property [$responsible_rg] not found on component`.

- [ ] **Step 3: Reescrever as regras de validação**

Em `app/Http/Requests/StoreEnrollmentRequest.php`, substitua `enrollmentRules()` por:

```php
    public static function enrollmentRules(array $state = []): array
    {
        $noFather = filter_var($state['student_no_father'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $noMother = filter_var($state['student_no_mother'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return [
            'responsible_name' => ['required', 'string', 'max:80', 'regex:/^[\pL\pN\s\'\-]+$/u'],
            'responsible_phone_number' => ['required', 'string', 'regex:/^\d{10,11}$/'],
            'responsible_home_phone' => ['nullable', 'string', 'regex:/^\d{10,11}$/'],
            'responsible_cpf' => ['required', 'string', 'regex:/^\d{11}$/', 'unique:responsibles,cpf'],
            'responsible_rg' => ['required', 'string', 'regex:/^\d{7,9}$/'],
            'responsible_email' => ['required', 'email', 'max:255'],
            'responsible_birth_date' => ['required', 'date', 'before:'.Carbon::now()->subYears(18)->format('Y-m-d')],
            'responsible_address' => ['required', 'string', 'max:150'],
            'responsible_neighborhood' => ['required', 'string', 'max:80'],
            'student_name' => ['required', 'string', 'max:80', 'regex:/^[\pL\pN\s\'\-]+$/u'],
            'student_cpf' => ['required', 'string', 'regex:/^\d{11}$/', 'unique:students,cpf'],
            'student_rg' => ['required', 'string', 'regex:/^\d{7,9}$/'],
            'student_birth_date' => [
                'required',
                'date',
                'before_or_equal:'.Carbon::now()->subYears(8)->format('Y-m-d'),
                'after:'.Carbon::now()->subYears(18)->format('Y-m-d'),
            ],
            'student_school' => ['required', 'string', 'max:120'],
            'student_grade' => ['required', 'string', 'max:30'],
            'student_father_name' => $noFather
                ? ['nullable']
                : ['required', 'string', 'max:80', 'regex:/^[\pL\pN\s\'\-]+$/u'],
            'student_mother_name' => $noMother
                ? ['nullable']
                : ['required', 'string', 'max:80', 'regex:/^[\pL\pN\s\'\-]+$/u'],
            'student_no_father' => ['boolean'],
            'student_no_mother' => ['boolean'],
            'student_phone' => ['nullable', 'string', 'regex:/^\d{10,11}$/'],
            'student_email' => ['nullable', 'email', 'max:255'],
            'student_modalidade' => ['required', 'string', 'in:Jiu Jitsu,Muay Thai,Taekwondo,Boxe'],
            'lgpd_consent' => ['accepted'],
        ];
    }
```

A obrigatoriedade da filiação é resolvida em PHP, e não com `required_unless`, porque `required_unless` compara o booleano do Livewire com a string `"true"` do parâmetro da regra — uma coerção frágil. Aqui a decisão é explícita.

- [ ] **Step 4: Acrescentar as mensagens em português**

Em `enrollmentMessages()`, do mesmo arquivo, acrescente as entradas abaixo ao array existente (não apague nenhuma das que já estão lá):

```php
            'responsible_home_phone.regex' => 'O telefone residencial deve conter 10 ou 11 dígitos.',
            'responsible_rg.required' => 'O RG do responsável é obrigatório — ele consta na autorização da ficha.',
            'responsible_rg.regex' => 'O RG deve conter de 7 a 9 dígitos numéricos, sem pontos ou traços.',
            'responsible_neighborhood.required' => 'O bairro é obrigatório.',
            'responsible_neighborhood.max' => 'O bairro não pode ter mais de 80 caracteres.',
            'student_rg.required' => 'O RG do aluno é obrigatório — ele consta na autorização da ficha.',
            'student_rg.regex' => 'O RG deve conter de 7 a 9 dígitos numéricos, sem pontos ou traços.',
            'student_school.required' => 'A escola do aluno é obrigatória.',
            'student_school.max' => 'O nome da escola não pode ter mais de 120 caracteres.',
            'student_grade.required' => 'A série do aluno é obrigatória.',
            'student_grade.max' => 'A série não pode ter mais de 30 caracteres.',
            'student_father_name.required' => 'Informe a filiação do pai ou marque que não possui.',
            'student_father_name.regex' => 'O nome do pai só pode conter letras, espaços, hífens e apóstrofos.',
            'student_mother_name.required' => 'Informe a filiação da mãe ou marque que não possui.',
            'student_mother_name.regex' => 'O nome da mãe só pode conter letras, espaços, hífens e apóstrofos.',
            'student_phone.regex' => 'O celular do aluno deve conter 10 ou 11 dígitos.',
            'student_email.email' => 'Informe um e-mail válido para o aluno ou deixe o campo vazio.',
```

- [ ] **Step 5: Declarar as propriedades novas no componente**

Em `app/Livewire/EnrollmentForm.php`, acrescente ao bloco de propriedades públicas, depois de `$responsible_address`:

```php
    public string $responsible_rg = '';

    public string $responsible_neighborhood = '';

    public ?string $responsible_home_phone = null;
```

e depois de `$student_birth_date`:

```php
    public string $student_school = '';

    public string $student_grade = '';

    public ?string $student_father_name = null;

    public bool $student_no_father = false;

    public ?string $student_mother_name = null;

    public bool $student_no_mother = false;

    public ?string $student_phone = null;

    public ?string $student_email = null;
```

Os campos opcionais são `?string` porque o Livewire entrega string vazia quando o input está em branco, e a regra `nullable` só ignora `null`. O passo seguinte normaliza `''` para `null`.

- [ ] **Step 6: Normalizar os opcionais e validar com o estado**

Ainda em `app/Livewire/EnrollmentForm.php`, acrescente este método privado à classe:

```php
    private function normalizeOptionalFields(): void
    {
        if ($this->student_no_father) {
            $this->student_father_name = null;
        }

        if ($this->student_no_mother) {
            $this->student_mother_name = null;
        }

        $optional = [
            'responsible_home_phone',
            'student_father_name',
            'student_mother_name',
            'student_phone',
            'student_email',
        ];

        foreach ($optional as $field) {
            if ($this->{$field} === '') {
                $this->{$field} = null;
            }
        }
    }
```

E no início de `submit()`, substitua o bloco

```php
        $validated = $this->validate(
            StoreEnrollmentRequest::enrollmentRules(),
            StoreEnrollmentRequest::enrollmentMessages()
        );
```

por

```php
        $this->normalizeOptionalFields();

        $validated = $this->validate(
            StoreEnrollmentRequest::enrollmentRules([
                'student_no_father' => $this->student_no_father,
                'student_no_mother' => $this->student_no_mother,
            ]),
            StoreEnrollmentRequest::enrollmentMessages()
        );

        if ($this->student_no_father && $this->student_no_mother) {
            $this->addError('student_no_father', 'É preciso informar pelo menos uma filiação.');

            return;
        }
```

A checagem das duas filiações vem depois do `validate()` porque este lança exceção ao falhar: se chegou aqui, os demais campos já estão válidos e o único erro exibido é o da filiação.

- [ ] **Step 7: Persistir os campos novos**

Ainda em `submit()`, dentro da transação, o `Responsible::create([...])` passa a ser:

```php
                $responsible = Responsible::create([
                    'name' => $validated['responsible_name'],
                    'phone_number' => $validated['responsible_phone_number'],
                    'home_phone' => $validated['responsible_home_phone'] ?? null,
                    'cpf' => $validated['responsible_cpf'],
                    'rg' => $validated['responsible_rg'],
                    'email' => $validated['responsible_email'],
                    'birth_date' => $validated['responsible_birth_date'],
                    'address' => $validated['responsible_address'],
                    'neighborhood' => $validated['responsible_neighborhood'],
                ]);
```

e o `Student::create([...])`:

```php
                $student = Student::create([
                    'responsible_id' => $responsible->id,
                    'name' => $validated['student_name'],
                    'cpf' => $validated['student_cpf'],
                    'rg' => $validated['student_rg'],
                    'birth_date' => $validated['student_birth_date'],
                    'school' => $validated['student_school'],
                    'grade' => $validated['student_grade'],
                    'father_name' => $validated['student_father_name'] ?? null,
                    'mother_name' => $validated['student_mother_name'] ?? null,
                    'no_father' => $this->student_no_father,
                    'no_mother' => $this->student_no_mother,
                    'phone' => $validated['student_phone'] ?? null,
                    'email' => $validated['student_email'] ?? null,
                    'modalidade' => $validated['student_modalidade'],
                ]);
```

Note que `'rg' => $validated['student_rg']` perde o `?: null` — o campo passou a ser obrigatório.

- [ ] **Step 8: Rodar o teste**

Run: `./vendor/bin/sail artisan test --filter EnrollmentTest`
Esperado: todos PASSAM, inclusive os que já existiam.

- [ ] **Step 9: Lint e commit (sugestão para o Paulo)**

```bash
./vendor/bin/sail exec laravel.test vendor/bin/pint
```

```bash
git add app/Http/Requests/StoreEnrollmentRequest.php app/Livewire/EnrollmentForm.php tests/Feature/EnrollmentTest.php && git commit -m "feat: validar campos da ficha e filiacao condicional na matricula"
```

---

### Task 6: Campos novos no formulário de matrícula

Tarefa só de interface: o back-end da Task 5 já aceita tudo isso. Os testes que garantem o comportamento já existem; aqui a verificação é visual e pelo Cypress na Task 10.

**Files:**
- Modify: `resources/views/livewire/enrollment-form.blade.php`

**Interfaces:**
- Consumes: as propriedades públicas declaradas na Task 5.
- Produces: inputs com os `data-cy` `input-responsible_rg`, `input-responsible_neighborhood`, `input-responsible_home_phone`, `input-student_school`, `input-student_grade`, `input-student_father_name`, `checkbox-student_no_father`, `input-student_mother_name`, `checkbox-student_no_mother`, `input-student_phone`, `input-student_email`.

- [ ] **Step 1: Acrescentar os campos do responsável**

No card "Dados do Responsável", dentro da `<div class="grid grid-cols-1 md:grid-cols-2 gap-6">`, logo depois do bloco do CPF, insira:

```blade
                        <div x-data="{
                            fmt(v) {
                                v = String(v||'').replace(/\D/g,'').substring(0,9);
                                return v.length>8 ? v.slice(0,2)+'.'+v.slice(2,5)+'.'+v.slice(5,8)+'-'+v.slice(8)
                                     : v.length>5 ? v.slice(0,2)+'.'+v.slice(2,5)+'.'+v.slice(5)
                                     : v.length>2 ? v.slice(0,2)+'.'+v.slice(2) : v;
                            }
                        }">
                            <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                RG <span class="text-red-400">*</span>
                            </label>
                            <input
                                type="text"
                                data-cy="input-responsible_rg"
                                maxlength="12"
                                inputmode="numeric"
                                placeholder="12.232.343-4"
                                x-effect="if (document.activeElement !== $el) $el.value = fmt($wire.responsible_rg)"
                                x-on:input="let r=$el.value.replace(/\D/g,'').substring(0,9); $el.value=fmt(r); $wire.set('responsible_rg',r);"
                                class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                       {{ $errors->has('responsible_rg') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                            >
                            @error('responsible_rg')
                                <p class="text-red-400 text-sm mt-1.5" data-cy="error-responsible_rg">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-data="{
                            fmt(v) {
                                v = String(v||'').replace(/\D/g,'').substring(0,11);
                                if (v.length > 10)
                                    return '('+v.slice(0,2)+') '+v.slice(2,3)+' '+v.slice(3,7)+'-'+v.slice(7);
                                if (v.length > 6)
                                    return '('+v.slice(0,2)+') '+v.slice(2,6)+'-'+v.slice(6);
                                if (v.length > 2)
                                    return '('+v.slice(0,2)+') '+v.slice(2);
                                return v.length ? '('+v : '';
                            }
                        }">
                            <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                Telefone residencial
                                <span class="text-neutral-600 text-xs">(opcional)</span>
                            </label>
                            <input
                                type="tel"
                                data-cy="input-responsible_home_phone"
                                maxlength="16"
                                inputmode="numeric"
                                placeholder="(42) 3224-1234"
                                x-effect="if (document.activeElement !== $el) $el.value = fmt($wire.responsible_home_phone)"
                                x-on:input="let r=$el.value.replace(/\D/g,'').substring(0,11); $el.value=fmt(r); $wire.set('responsible_home_phone',r);"
                                class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                       {{ $errors->has('responsible_home_phone') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                            >
                            @error('responsible_home_phone')
                                <p class="text-red-400 text-sm mt-1.5" data-cy="error-responsible_home_phone">{{ $message }}</p>
                            @enderror
                        </div>
```

E logo depois do bloco do endereço, insira:

```blade
                        <div>
                            <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                Bairro <span class="text-red-400">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="responsible_neighborhood"
                                data-cy="input-responsible_neighborhood"
                                maxlength="80"
                                class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                       {{ $errors->has('responsible_neighborhood') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                            >
                            @error('responsible_neighborhood')
                                <p class="text-red-400 text-sm mt-1.5" data-cy="error-responsible_neighborhood">{{ $message }}</p>
                            @enderror
                        </div>
```

- [ ] **Step 2: Tornar o RG do aluno obrigatório na interface**

No card "Dados do Aluno", no bloco do RG, troque

```blade
                                RG
                                <span class="text-neutral-600 text-xs">(opcional)</span>
```

por

```blade
                                RG <span class="text-red-400">*</span>
```

- [ ] **Step 3: Acrescentar escola, série e contatos do aluno**

Ainda no card "Dados do Aluno", depois do bloco da data de nascimento, insira:

```blade
                        <div>
                            <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                Escola <span class="text-red-400">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="student_school"
                                data-cy="input-student_school"
                                maxlength="120"
                                class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                       {{ $errors->has('student_school') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                            >
                            @error('student_school')
                                <p class="text-red-400 text-sm mt-1.5" data-cy="error-student_school">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                Série <span class="text-red-400">*</span>
                            </label>
                            <input
                                type="text"
                                wire:model="student_grade"
                                data-cy="input-student_grade"
                                maxlength="30"
                                placeholder="5º ano"
                                class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                       {{ $errors->has('student_grade') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                            >
                            @error('student_grade')
                                <p class="text-red-400 text-sm mt-1.5" data-cy="error-student_grade">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-data="{
                            fmt(v) {
                                v = String(v||'').replace(/\D/g,'').substring(0,11);
                                if (v.length > 10)
                                    return '('+v.slice(0,2)+') '+v.slice(2,3)+' '+v.slice(3,7)+'-'+v.slice(7);
                                if (v.length > 6)
                                    return '('+v.slice(0,2)+') '+v.slice(2,6)+'-'+v.slice(6);
                                if (v.length > 2)
                                    return '('+v.slice(0,2)+') '+v.slice(2);
                                return v.length ? '('+v : '';
                            }
                        }">
                            <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                Celular do aluno
                                <span class="text-neutral-600 text-xs">(opcional)</span>
                            </label>
                            <input
                                type="tel"
                                data-cy="input-student_phone"
                                maxlength="16"
                                inputmode="numeric"
                                placeholder="(42) 9 9999-9999"
                                x-effect="if (document.activeElement !== $el) $el.value = fmt($wire.student_phone)"
                                x-on:input="let r=$el.value.replace(/\D/g,'').substring(0,11); $el.value=fmt(r); $wire.set('student_phone',r);"
                                class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                       {{ $errors->has('student_phone') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                            >
                            @error('student_phone')
                                <p class="text-red-400 text-sm mt-1.5" data-cy="error-student_phone">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                E-mail do aluno
                                <span class="text-neutral-600 text-xs">(opcional)</span>
                            </label>
                            <input
                                type="email"
                                wire:model="student_email"
                                data-cy="input-student_email"
                                maxlength="255"
                                autocomplete="off"
                                class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                       {{ $errors->has('student_email') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                            >
                            @error('student_email')
                                <p class="text-red-400 text-sm mt-1.5" data-cy="error-student_email">{{ $message }}</p>
                            @enderror
                        </div>
```

- [ ] **Step 4: Acrescentar a filiação com os checkboxes**

Ainda no card "Dados do Aluno", depois dos blocos do passo anterior e antes do bloco da modalidade, insira:

```blade
                        <div class="md:col-span-2 border-t border-neutral-800 pt-6">
                            <p class="text-sm font-medium text-neutral-300 mb-4">Filiação</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                <div>
                                    <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                        Nome do pai <span class="text-red-400">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        wire:model="student_father_name"
                                        data-cy="input-student_father_name"
                                        maxlength="80"
                                        x-bind:disabled="$wire.student_no_father"
                                        x-on:input="$el.value = $el.value.replace(/[^a-zA-ZÀ-ÿ '\-]/g, '')"
                                        class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                               disabled:opacity-40 disabled:cursor-not-allowed
                                               {{ $errors->has('student_father_name') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                                    >
                                    <label class="flex items-center gap-2 mt-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            wire:model.live="student_no_father"
                                            data-cy="checkbox-student_no_father"
                                            class="w-4 h-4 accent-white cursor-pointer"
                                        >
                                        <span class="text-sm text-neutral-500">Não possui pai registrado</span>
                                    </label>
                                    @error('student_father_name')
                                        <p class="text-red-400 text-sm mt-1.5" data-cy="error-student_father_name">{{ $message }}</p>
                                    @enderror
                                    @error('student_no_father')
                                        <p class="text-red-400 text-sm mt-1.5" data-cy="error-student_no_father">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-neutral-400 mb-1.5">
                                        Nome da mãe <span class="text-red-400">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        wire:model="student_mother_name"
                                        data-cy="input-student_mother_name"
                                        maxlength="80"
                                        x-bind:disabled="$wire.student_no_mother"
                                        x-on:input="$el.value = $el.value.replace(/[^a-zA-ZÀ-ÿ '\-]/g, '')"
                                        class="bg-neutral-900 border focus:outline-none text-white rounded-lg px-4 py-2.5 w-full transition
                                               disabled:opacity-40 disabled:cursor-not-allowed
                                               {{ $errors->has('student_mother_name') ? 'border-red-500/60' : 'border-neutral-800 focus:border-neutral-600' }}"
                                    >
                                    <label class="flex items-center gap-2 mt-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            wire:model.live="student_no_mother"
                                            data-cy="checkbox-student_no_mother"
                                            class="w-4 h-4 accent-white cursor-pointer"
                                        >
                                        <span class="text-sm text-neutral-500">Não possui mãe registrada</span>
                                    </label>
                                    @error('student_mother_name')
                                        <p class="text-red-400 text-sm mt-1.5" data-cy="error-student_mother_name">{{ $message }}</p>
                                    @enderror
                                </div>

                            </div>
                        </div>
```

O `wire:model.live` nos checkboxes é o que faz `$wire.student_no_father` mudar de imediato, para o `x-bind:disabled` do input reagir. Um input desabilitado não é enviado, então o Livewire mantém o último valor digitado; quem garante o `null` é o `normalizeOptionalFields()` da Task 5.

- [ ] **Step 5: Atualizar o texto do aceite LGPD**

No card do aceite, troque o trecho

```blade
                            exclusivamente para geração do termo de aceite para assinatura e
                            para controle interno do CTM, conforme a
```

por

```blade
                            exclusivamente para a geração da ficha de cadastro de atleta exigida
                            pela Secretaria Municipal de Esportes e Recreação de Prudentópolis e
                            para controle interno do projeto, conforme a
```

- [ ] **Step 6: Conferir no navegador**

Com `./vendor/bin/sail npm run dev` rodando, abra `/enrollment` e verifique: os campos novos aparecem, o RG do aluno tem asterisco, marcar "Não possui pai registrado" apaga e desabilita o input, desmarcar reabilita, e o envio completo cria o cadastro.

- [ ] **Step 7: Rodar a suíte**

Run: `./vendor/bin/sail artisan test`
Esperado: tudo passa.

- [ ] **Step 8: Commit (sugestão para o Paulo)**

```bash
git add resources/views/livewire/enrollment-form.blade.php && git commit -m "feat: campos da ficha smer no formulario de matricula"
```

---

### Task 7: Centralizar os formatadores

As closures de formatação de CPF e telefone vivem hoje dentro de um `@php` em `dashboard.blade.php`. A ficha precisa exatamente das mesmas regras, e copiá-las para uma segunda view garantiria que um dia divergissem.

**Files:**
- Create: `app/Support/Formatters.php`
- Modify: `resources/views/livewire/admin/dashboard.blade.php:16-45`
- Test: `tests/Unit/FormattersTest.php`

**Interfaces:**
- Consumes: nada.
- Produces: `App\Support\Formatters` com os métodos estáticos `cpf(?string): string`, `rg(?string): string`, `phone(?string): string`, `date(?DateTimeInterface): string`. Todos devolvem string vazia para entrada vazia e o valor original quando o tamanho não bate com o esperado.

- [ ] **Step 1: Escrever o teste que falha**

Crie `tests/Unit/FormattersTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Support\Formatters;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class FormattersTest extends TestCase
{
    public function test_formats_cpf(): void
    {
        $this->assertSame('123.456.789-09', Formatters::cpf('12345678909'));
    }

    public function test_returns_original_cpf_when_length_is_wrong(): void
    {
        $this->assertSame('123', Formatters::cpf('123'));
    }

    public function test_formats_rg(): void
    {
        $this->assertSame('12.232.343-4', Formatters::rg('122323434'));
    }

    public function test_formats_mobile_phone(): void
    {
        $this->assertSame('(11) 98765-4321', Formatters::phone('11987654321'));
    }

    public function test_formats_landline_phone(): void
    {
        $this->assertSame('(42) 3224-1234', Formatters::phone('4232241234'));
    }

    public function test_formats_date(): void
    {
        $this->assertSame('10/06/2015', Formatters::date(Carbon::parse('2015-06-10')));
    }

    public function test_empty_values_become_empty_strings(): void
    {
        $this->assertSame('', Formatters::cpf(null));
        $this->assertSame('', Formatters::phone(''));
        $this->assertSame('', Formatters::rg(null));
        $this->assertSame('', Formatters::date(null));
    }
}
```

- [ ] **Step 2: Rodar e confirmar a falha**

Run: `./vendor/bin/sail artisan test --filter FormattersTest`
Esperado: FALHA com `Class "App\Support\Formatters" not found`.

- [ ] **Step 3: Escrever a classe**

Crie `app/Support/Formatters.php`:

```php
<?php

namespace App\Support;

use DateTimeInterface;

class Formatters
{
    public static function cpf(?string $cpf): string
    {
        $digits = self::digits($cpf);

        return strlen($digits) === 11
            ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits)
            : (string) $cpf;
    }

    public static function rg(?string $rg): string
    {
        $digits = self::digits($rg);

        return strlen($digits) === 9
            ? preg_replace('/(\d{2})(\d{3})(\d{3})(\d)/', '$1.$2.$3-$4', $digits)
            : (string) $rg;
    }

    public static function phone(?string $phone): string
    {
        $digits = self::digits($phone);

        return match (strlen($digits)) {
            11 => preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $digits),
            10 => preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $digits),
            default => (string) $phone,
        };
    }

    public static function date(?DateTimeInterface $date): string
    {
        return $date?->format('d/m/Y') ?? '';
    }

    private static function digits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value);
    }
}
```

- [ ] **Step 4: Rodar o teste**

Run: `./vendor/bin/sail artisan test --filter FormattersTest`
Esperado: 7 testes PASSAM.

- [ ] **Step 5: Usar a classe no dashboard**

Em `resources/views/livewire/admin/dashboard.blade.php`, dentro do bloco `@php`, apague as definições de `$formatCpf` e `$formatPhone` e passe a usar a classe. O bloco `@php` fica assim:

```blade
    @php
        use App\Support\Formatters;

        $statusBadge = fn (string $status) => match ($status) {
            'entregue' => 'bg-yellow-950 text-yellow-400 border-yellow-800',
            'assinado' => 'bg-green-950 text-green-400 border-green-800',
            default    => 'bg-red-950 text-red-400 border-red-800',
        };
        $modalData = fn ($s) => [
            'id'           => $s->id,
            'name'         => $s->name,
            'cpf'          => Formatters::cpf($s->cpf),
            'rg'           => Formatters::rg($s->rg) ?: '—',
            'birth_date'   => Formatters::date($s->birth_date),
            'modalidade'   => $s->modalidade,
            'termo_status' => $s->termo_status,
            'resp'         => [
                'name'       => $s->responsible->name,
                'phone'      => Formatters::phone($s->responsible->phone_number),
                'cpf'        => Formatters::cpf($s->responsible->cpf),
                'email'      => $s->responsible->email,
                'birth_date' => Formatters::date($s->responsible->birth_date),
                'address'    => $s->responsible->address,
            ],
        ];
    @endphp
```

- [ ] **Step 6: Trocar a chamada restante na tabela**

Ainda no mesmo arquivo, na célula de contato da tabela, troque

```blade
                            <td class="px-6 py-4 text-neutral-400 font-mono">{{ $formatPhone($student->responsible->phone_number) }}</td>
```

por

```blade
                            <td class="px-6 py-4 text-neutral-400 font-mono">{{ Formatters::phone($student->responsible->phone_number) }}</td>
```

Depois procure por outras ocorrências e troque todas:

```bash
grep -n 'formatCpf\|formatPhone' resources/views/livewire/admin/dashboard.blade.php
```

Esperado ao final: nenhum resultado.

- [ ] **Step 7: Rodar os testes do dashboard**

Run: `./vendor/bin/sail artisan test --filter DashboardTest`
Esperado: todos PASSAM — em especial `test_dashboard_shows_responsible_name_and_phone`, que confere o telefone formatado.

- [ ] **Step 8: Lint e commit (sugestão para o Paulo)**

```bash
./vendor/bin/sail exec laravel.test vendor/bin/pint
```

```bash
git add app/Support tests/Unit/FormattersTest.php resources/views/livewire/admin/dashboard.blade.php && git commit -m "refactor: extrair formatadores de cpf, rg, telefone e data"
```

---

### Task 8: Geração da ficha em PDF

**Files:**
- Create: `app/Http/Controllers/StudentFichaController.php`
- Create: `resources/views/pdf/ficha-atleta.blade.php`
- Modify: `routes/web.php`
- Modify: `composer.json` (via `composer require`)
- Test: `tests/Feature/StudentFichaTest.php`

**Interfaces:**
- Consumes: `Student` e `Responsible` com os campos da ficha (Task 1), `App\Support\Formatters` (Task 7), o grupo de rotas `auth` (Task 4).
- Produces: rota nomeada `admin.students.ficha`, em `GET /admin/alunos/{student}/ficha`, devolvendo `application/pdf`.

- [ ] **Step 1: Instalar o dompdf**

Run: `./vendor/bin/sail composer require barryvdh/laravel-dompdf`

Esperado: pacote instalado e descoberto automaticamente. Confira com:

```bash
./vendor/bin/sail artisan about | grep -i dompdf
```

- [ ] **Step 2: Escrever o teste que falha**

Crie `tests/Feature/StudentFichaTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Responsible;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentFichaTest extends TestCase
{
    use RefreshDatabase;

    private function student(array $studentAttributes = [], array $responsibleAttributes = []): Student
    {
        $responsible = Responsible::factory()->create(array_merge([
            'name' => 'Maria da Silva',
            'rg' => '111111111',
            'address' => 'Rua das Flores, 123',
            'neighborhood' => 'Centro',
            'phone_number' => '42999998888',
            'home_phone' => '4232241234',
            'email' => 'maria@example.com',
        ], $responsibleAttributes));

        return Student::factory()->create(array_merge([
            'responsible_id' => $responsible->id,
            'name' => 'João da Silva',
            'rg' => '222222222',
            'birth_date' => '2015-06-10',
            'school' => 'Colégio Estadual de Prudentópolis',
            'grade' => '5º ano',
            'father_name' => 'José da Silva',
            'mother_name' => 'Maria da Silva',
            'modalidade' => 'Boxe',
        ], $studentAttributes));
    }

    public function test_guest_cannot_download_the_ficha(): void
    {
        $student = $this->student();

        $this->get(route('admin.students.ficha', $student))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_downloads_a_pdf(): void
    {
        $this->actingAs(User::factory()->create());
        $student = $this->student();

        $response = $this->get(route('admin.students.ficha', $student));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_ficha_shows_the_student_data(): void
    {
        $student = $this->student();

        $this->view('pdf.ficha-atleta', [
            'student' => $student,
            'responsible' => $student->responsible,
            'issuedAt' => Carbon::parse('2026-08-24'),
        ])
            ->assertSee('João da Silva')
            ->assertSee('22.222.222-2')
            ->assertSee('10/06/2015')
            ->assertSee('Colégio Estadual de Prudentópolis')
            ->assertSee('5º ano')
            ->assertSee('José da Silva')
            ->assertSee('Centro')
            ->assertSee('(42) 3224-1234');
    }

    public function test_ficha_shows_the_authorization_with_both_rgs(): void
    {
        $student = $this->student();

        $this->view('pdf.ficha-atleta', [
            'student' => $student,
            'responsible' => $student->responsible,
            'issuedAt' => Carbon::parse('2026-08-24'),
        ])
            ->assertSee('AUTORIZAÇÃO DE PARTICIPAÇÃO')
            ->assertSee('Maria da Silva')
            ->assertSee('11.111.111-1')
            ->assertSee('22.222.222-2');
    }

    public function test_ficha_title_follows_the_student_modalidade(): void
    {
        $student = $this->student(['modalidade' => 'Muay Thai']);

        $this->view('pdf.ficha-atleta', [
            'student' => $student,
            'responsible' => $student->responsible,
            'issuedAt' => Carbon::parse('2026-08-24'),
        ])
            ->assertSee('FICHA DE CADASTRO DE ATLETA')
            ->assertSee('MUAY THAI');
    }

    public function test_ficha_prints_the_issue_date_in_portuguese(): void
    {
        $student = $this->student();

        $this->view('pdf.ficha-atleta', [
            'student' => $student,
            'responsible' => $student->responsible,
            'issuedAt' => Carbon::parse('2026-08-24'),
        ])->assertSee('Prudentópolis, 24 de agosto de 2026');
    }

    public function test_declared_missing_father_prints_nao_declarado(): void
    {
        $student = $this->student([
            'father_name' => null,
            'no_father' => true,
        ]);

        $this->view('pdf.ficha-atleta', [
            'student' => $student,
            'responsible' => $student->responsible,
            'issuedAt' => Carbon::parse('2026-08-24'),
        ])->assertSee('Não declarado');
    }

    public function test_ficha_keeps_the_signature_notice(): void
    {
        $student = $this->student();

        $this->view('pdf.ficha-atleta', [
            'student' => $student,
            'responsible' => $student->responsible,
            'issuedAt' => Carbon::parse('2026-08-24'),
        ])
            ->assertSee('Assinatura do pai ou responsável')
            ->assertSee('Assinatura do atleta');
    }
}
```

- [ ] **Step 3: Rodar e confirmar a falha**

Run: `./vendor/bin/sail artisan test --filter StudentFichaTest`
Esperado: FALHA com `Route [admin.students.ficha] not defined`.

- [ ] **Step 4: Escrever o controller**

Crie `app/Http/Controllers/StudentFichaController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class StudentFichaController extends Controller
{
    public function __invoke(Student $student): Response
    {
        $student->load('responsible');

        return Pdf::loadView('pdf.ficha-atleta', [
            'student' => $student,
            'responsible' => $student->responsible,
            'issuedAt' => now(),
        ])
            ->setPaper('a4')
            ->download('ficha-'.Str::slug($student->name).'.pdf');
    }
}
```

- [ ] **Step 5: Registrar a rota**

Em `routes/web.php`, dentro do grupo `Route::middleware('auth')`, logo abaixo da rota do dashboard, acrescente:

```php
    Route::get('/admin/alunos/{student}/ficha', StudentFichaController::class)
        ->name('admin.students.ficha');
```

e o import no topo:

```php
use App\Http\Controllers\StudentFichaController;
```

O `{student}` resolve pelo UUID da chave primária, porque `Student` usa `HasUuids`.

- [ ] **Step 6: Escrever a view da ficha**

Crie `resources/views/pdf/ficha-atleta.blade.php`:

```blade
@php
    use App\Support\Formatters;
    use Illuminate\Support\Str;

    $meses = [
        1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
        'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro',
    ];

    $dia = $issuedAt->format('d');
    $mes = $meses[(int) $issuedAt->format('n')];
    $ano = $issuedAt->format('Y');

    $filiacao = fn (?string $nome, bool $naoPossui) => $naoPossui ? 'Não declarado' : ($nome ?: '');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Ficha de Cadastro de Atleta — {{ $student->name }}</title>
    <style>
        @page { margin: 25mm 18mm; }
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10.5pt;
            color: #000;
            line-height: 1.45;
        }
        .cabecalho { width: 100%; margin-bottom: 26px; }
        .cabecalho td { vertical-align: middle; }
        .municipio { font-size: 7pt; letter-spacing: 2px; color: #444; }
        .municipio strong { display: block; font-size: 17pt; letter-spacing: 0; color: #000; }
        .smer {
            background: #4a4a4a;
            color: #fff;
            padding: 6px 12px;
            font-size: 11pt;
            font-weight: bold;
            line-height: 1.15;
            text-align: center;
        }
        .smer span { display: block; font-size: 6.5pt; font-weight: normal; letter-spacing: 1px; }
        h1 { font-size: 12pt; font-weight: normal; text-align: center; margin: 0 0 4px; }
        h2 { font-size: 11pt; font-weight: normal; text-align: center; margin: 0 0 12px; }
        table.dados { width: 100%; border-collapse: collapse; margin-bottom: 26px; }
        table.dados th {
            border: 1px solid #000;
            font-weight: normal;
            text-align: center;
            padding: 3px 6px;
        }
        table.dados td { border: 1px solid #000; padding: 4px 6px; }
        .rotulo { color: #000; }
        .valor { font-weight: bold; }
        h3 { font-size: 11pt; font-weight: normal; text-align: center; margin: 0 0 12px; }
        p.autorizacao { text-align: justify; text-indent: 40px; margin: 0 0 16px; }
        p.declaracao { text-indent: 40px; margin: 0 0 40px; }
        p.local { text-indent: 40px; margin: 0 0 60px; }
        table.assinaturas { width: 100%; margin-bottom: 30px; }
        table.assinaturas td { width: 50%; text-align: center; padding: 0 10px; }
        .linha { border-top: 1px solid #000; padding-top: 4px; }
        p.obs { font-size: 9pt; margin: 0; }
    </style>
</head>
<body>

    <table class="cabecalho">
        <tr>
            <td class="municipio">MUNICÍPIO DE<strong>PRUDENTÓPOLIS</strong></td>
            <td style="text-align: right;">
                <div class="smer"><span>SECRETARIA DE</span>ESPORTES E RECREAÇÃO</div>
            </td>
        </tr>
    </table>

    <h1>FICHA DE CADASTRO DE ATLETA</h1>
    <h2>{{ Str::upper($student->modalidade) }} &ndash; {{ $ano }}</h2>

    <table class="dados">
        <tr>
            <th colspan="4">DADOS DO ATLETA</th>
        </tr>
        <tr>
            <td colspan="4"><span class="rotulo">Nome do Atleta:</span> <span class="valor">{{ $student->name }}</span></td>
        </tr>
        <tr>
            <td colspan="2"><span class="rotulo">R.G.:</span> <span class="valor">{{ Formatters::rg($student->rg) }}</span></td>
            <td colspan="2"><span class="rotulo">Data Nasc.:</span> <span class="valor">{{ Formatters::date($student->birth_date) }}</span></td>
        </tr>
        <tr>
            <td colspan="2"><span class="rotulo">Escola:</span> <span class="valor">{{ $student->school }}</span></td>
            <td colspan="2"><span class="rotulo">Série:</span> <span class="valor">{{ $student->grade }}</span></td>
        </tr>
        <tr>
            <td colspan="4"><span class="rotulo">Filiação Pai:</span> <span class="valor">{{ $filiacao($student->father_name, $student->no_father) }}</span></td>
        </tr>
        <tr>
            <td colspan="4"><span class="rotulo">Mãe:</span> <span class="valor">{{ $filiacao($student->mother_name, $student->no_mother) }}</span></td>
        </tr>
        <tr>
            <td colspan="3"><span class="rotulo">Endereço:</span> <span class="valor">{{ $responsible->address }}</span></td>
            <td><span class="rotulo">Bairro:</span> <span class="valor">{{ $responsible->neighborhood }}</span></td>
        </tr>
        <tr>
            <td><span class="rotulo">Fone resid.:</span> <span class="valor">{{ Formatters::phone($responsible->home_phone) }}</span></td>
            <td><span class="rotulo">Cel pais:</span> <span class="valor">{{ Formatters::phone($responsible->phone_number) }}</span></td>
            <td colspan="2"><span class="rotulo">Cel atleta:</span> <span class="valor">{{ Formatters::phone($student->phone) }}</span></td>
        </tr>
        <tr>
            <td colspan="2"><span class="rotulo">e-mail atleta:</span> <span class="valor">{{ $student->email }}</span></td>
            <td colspan="2"><span class="rotulo">Pais:</span> <span class="valor">{{ $responsible->email }}</span></td>
        </tr>
    </table>

    <h3>AUTORIZAÇÃO DE PARTICIPAÇÃO</h3>

    <p class="autorizacao">
        Eu, <strong>{{ $responsible->name }}</strong>, portador da Cédula de Identidade RG nº
        <strong>{{ Formatters::rg($responsible->rg) }}</strong>, na qualidade de pai (mãe) ou responsável pelo menor
        <strong>{{ $student->name }}</strong>, portador da Cédula de Identidade RG nº
        <strong>{{ Formatters::rg($student->rg) }}</strong>, declaro que o mesmo está autorizado a participar dos
        treinos e competições organizados pela Secretaria Municipal de Esportes e Recreação &ndash; SMER &ndash; e que
        goza de perfeita saúde para a prática esportiva, isentando a SMER, seus professores e demais envolvidos de
        quaisquer problemas oriundos de acidentes ou outras ocorrências no decorrer dos treinos ou competições.
    </p>

    <p class="declaracao">Pela verdade, firmo a presente declaração.</p>

    <p class="local">Prudentópolis, {{ $dia }} de {{ $mes }} de {{ $ano }}.</p>

    <table class="assinaturas">
        <tr>
            <td><div class="linha">Assinatura do pai ou responsável</div></td>
            <td><div class="linha">Assinatura do atleta</div></td>
        </tr>
    </table>

    <p class="obs">
        <strong>Obs:</strong> O atleta só poderá participar dos treinos após entregar a ficha preenchida
        e assinada ao professor.
    </p>

</body>
</html>
```

O cabeçalho é montado em HTML porque os arquivos do brasão do município e do logo da SMER não estão no repositório. Quando estiverem, substitua o conteúdo das duas células da tabela `.cabecalho` por `<img src="{{ public_path('img/brasao.png') }}" style="height: 42px;">` — o dompdf lê caminhos de arquivo locais, não URLs.

Os meses vêm de um array literal em vez de `translatedFormat()` do Carbon: elimina a dependência da localidade configurada no ambiente, e são doze strings.

- [ ] **Step 7: Rodar o teste**

Run: `./vendor/bin/sail artisan test --filter StudentFichaTest`
Esperado: 8 testes PASSAM.

- [ ] **Step 8: Conferir o PDF de verdade**

Logue em `/login`, pegue o UUID de um aluno no dashboard e acesse `/admin/alunos/{uuid}/ficha`. Abra o arquivo baixado e compare com a foto da ficha oficial: cabeçalho, ordem das linhas da tabela, texto da autorização e as duas linhas de assinatura. Confira principalmente a acentuação — se sair quebrada, o problema é a fonte, não o texto.

- [ ] **Step 9: Lint e commit (sugestão para o Paulo)**

```bash
./vendor/bin/sail exec laravel.test vendor/bin/pint
```

```bash
git add composer.json composer.lock app/Http/Controllers/StudentFichaController.php resources/views/pdf routes/web.php tests/Feature/StudentFichaTest.php && git commit -m "feat: gerar ficha de cadastro smer em pdf"
```

---

### Task 9: Botão de gerar ficha no dashboard

**Files:**
- Modify: `resources/views/livewire/admin/dashboard.blade.php`
- Test: `tests/Feature/Admin/DashboardTest.php`

**Interfaces:**
- Consumes: a rota `admin.students.ficha` (Task 8).
- Produces: link para a ficha em cada linha da tabela e dentro do modal de detalhes.

- [ ] **Step 1: Escrever o teste que falha**

Acrescente a `tests/Feature/Admin/DashboardTest.php`:

```php
    public function test_dashboard_links_to_the_student_ficha(): void
    {
        $this->actingAs(User::factory()->create());
        $student = Student::factory()->create();

        Livewire::test(Dashboard::class)
            ->assertSee(route('admin.students.ficha', $student), escape: false);
    }
```

- [ ] **Step 2: Rodar e confirmar a falha**

Run: `./vendor/bin/sail artisan test --filter test_dashboard_links_to_the_student_ficha`
Esperado: FALHA — a URL não aparece no HTML.

- [ ] **Step 3: Acrescentar a coluna na tabela**

Em `resources/views/livewire/admin/dashboard.blade.php`, na `<thead>`, depois do `<th>` de "Status do Termo":

```blade
                        <th class="px-6 py-4 text-left">Ficha</th>
```

E na `<tbody>`, depois da `<td>` do status:

```blade
                            <td class="px-6 py-4">
                                <a
                                    href="{{ route('admin.students.ficha', $student) }}"
                                    target="_blank"
                                    @click.stop
                                    data-cy="ficha-link"
                                    class="inline-block text-xs font-semibold border border-neutral-700 text-neutral-300 hover:bg-neutral-800 hover:text-white px-3 py-1.5 rounded-lg transition"
                                >
                                    Gerar ficha
                                </a>
                            </td>
```

O `@click.stop` é obrigatório: a `<tr>` inteira já tem `@click="select(...)"`, e sem ele clicar no link abriria o modal de detalhes junto.

- [ ] **Step 4: Acrescentar o botão ao modal**

O modal é client-side e recebe os dados por `$modalData`, que já inclui `id`. Localize o `<select>` do status do termo (aquele com `@change="$wire.updateTermoStatus(s.id, s.termo_status)"`) e, logo depois da `</div>` que fecha o bloco desse seletor — imediatamente antes do comentário `{{-- Dados do Responsável --}}` — insira:

```blade
                <a
                    :href="'/admin/alunos/' + s.id + '/ficha'"
                    target="_blank"
                    data-cy="ficha-link-modal"
                    class="inline-block bg-white text-black font-bold px-6 py-2.5 rounded-lg hover:bg-neutral-200 transition"
                >
                    Gerar ficha
                </a>
```

A URL é montada em JavaScript porque, dentro do modal, `s` é um objeto Alpine e não há `$student` do Blade disponível.

- [ ] **Step 5: Mostrar os campos novos no modal**

O modal exibe hoje um subconjunto dos dados e ficou defasado em relação à ficha. Em `$modalData`, dentro do `@php`, acrescente as chaves novas — o array passa a ser:

```blade
        $modalData = fn ($s) => [
            'id'           => $s->id,
            'name'         => $s->name,
            'cpf'          => Formatters::cpf($s->cpf),
            'rg'           => Formatters::rg($s->rg) ?: '—',
            'birth_date'   => Formatters::date($s->birth_date),
            'school'       => $s->school ?: '—',
            'grade'        => $s->grade ?: '—',
            'father_name'  => $s->no_father ? 'Não declarado' : ($s->father_name ?: '—'),
            'mother_name'  => $s->no_mother ? 'Não declarado' : ($s->mother_name ?: '—'),
            'phone'        => Formatters::phone($s->phone) ?: '—',
            'email'        => $s->email ?: '—',
            'modalidade'   => $s->modalidade,
            'termo_status' => $s->termo_status,
            'resp'         => [
                'name'         => $s->responsible->name,
                'phone'        => Formatters::phone($s->responsible->phone_number),
                'home_phone'   => Formatters::phone($s->responsible->home_phone) ?: '—',
                'cpf'          => Formatters::cpf($s->responsible->cpf),
                'rg'           => Formatters::rg($s->responsible->rg) ?: '—',
                'email'        => $s->responsible->email,
                'birth_date'   => Formatters::date($s->responsible->birth_date),
                'address'      => $s->responsible->address,
                'neighborhood' => $s->responsible->neighborhood ?: '—',
            ],
        ];
```

Na seção "Responsável" do modal, acrescente ao `<dl>`, depois do bloco do CPF:

```blade
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">RG</dt>
                            <dd class="mt-0.5 font-mono text-neutral-200" x-text="s.resp.rg"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">Telefone residencial</dt>
                            <dd class="mt-0.5 font-mono text-neutral-200" x-text="s.resp.home_phone"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">Bairro</dt>
                            <dd class="mt-0.5 text-neutral-200" x-text="s.resp.neighborhood"></dd>
                        </div>
```

E na seção "Estudante", depois do bloco da data de nascimento:

```blade
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">Escola</dt>
                            <dd class="mt-0.5 text-neutral-200" x-text="s.school"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">Série</dt>
                            <dd class="mt-0.5 text-neutral-200" x-text="s.grade"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">Filiação — Pai</dt>
                            <dd class="mt-0.5 text-neutral-200" x-text="s.father_name"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">Filiação — Mãe</dt>
                            <dd class="mt-0.5 text-neutral-200" x-text="s.mother_name"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">Celular do aluno</dt>
                            <dd class="mt-0.5 font-mono text-neutral-200" x-text="s.phone"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-neutral-500">E-mail do aluno</dt>
                            <dd class="mt-0.5 break-all text-neutral-200" x-text="s.email"></dd>
                        </div>
```

- [ ] **Step 6: Rodar os testes do dashboard**

Run: `./vendor/bin/sail artisan test --filter DashboardTest`
Esperado: todos PASSAM.

- [ ] **Step 7: Conferir no navegador**

No dashboard, clique em "Gerar ficha" numa linha: o PDF deve baixar **sem** abrir o modal. Depois abra o modal clicando na linha, confira os campos novos e use o botão de lá.

- [ ] **Step 8: Commit (sugestão para o Paulo)**

```bash
git add resources/views/livewire/admin/dashboard.blade.php tests/Feature/Admin/DashboardTest.php && git commit -m "feat: botao de gerar ficha e campos da ficha no dashboard admin"
```

---

### Task 10: Cypress

A suíte Cypress está quebrada desde antes deste trabalho: o `baseUrl` aponta para uma URL de GitHub Codespaces que não existe mais, e três testes esperam a URL `/matricula` quando a rota do projeto é `/enrollment`. Consertar isso é pré-requisito para poder cobrir os campos novos.

**Files:**
- Modify: `cypress.config.js`
- Modify: `cypress/e2e/enrollment.cy.js`
- Create: `cypress/e2e/login.cy.js`

**Interfaces:**
- Consumes: os `data-cy` das Tasks 3 e 6.
- Produces: suíte E2E executável contra `http://localhost:8001`.

- [ ] **Step 1: Apontar o Cypress para o servidor local**

Em `cypress.config.js`, troque

```js
    baseUrl: 'https://verbose-space-cod-rjvrw5v6pvgfx6j7-8001.app.github.dev/',
```

por

```js
    baseUrl: process.env.CYPRESS_BASE_URL || 'http://localhost:8001',
```

- [ ] **Step 2: Corrigir a URL da matrícula**

Em `cypress/e2e/enrollment.cy.js`, troque todas as ocorrências de `/matricula` por `/enrollment`:

```bash
sed -i "s#/matricula#/enrollment#g" cypress/e2e/enrollment.cy.js
```

Confira que sobraram zero ocorrências:

```bash
grep -c "matricula" cypress/e2e/enrollment.cy.js
```

Esperado: `0`.

- [ ] **Step 3: Acrescentar os campos novos aos helpers**

No topo de `cypress/e2e/enrollment.cy.js`, os dois construtores de dados passam a ser:

```js
const responsible = () => ({
  name: 'Maria da Silva',
  phone: '11999999999',
  homePhone: '4232241234',
  cpf: randomCpf(),
  rg: '111111111',
  email: `maria${Date.now()}@email.com`,
  birthDate: '1990-01-15',
  address: 'Rua das Flores, 123',
  neighborhood: 'Centro',
});

const student = () => ({
  name: 'João da Silva',
  cpf: randomCpf(),
  rg: '222222222',
  birthDate: '2015-06-10',
  school: 'Colégio Estadual de Prudentópolis',
  grade: '5º ano',
  fatherName: 'José da Silva',
  motherName: 'Maria da Silva',
  phone: '42999998888',
  email: 'joao@email.com',
  modalidade: 'Jiu Jitsu',
});
```

Onde o arquivo já preenche o formulário campo a campo, acrescente os inputs novos seguindo o padrão que já está lá:

```js
    cy.get('[data-cy="input-responsible_rg"]').type(r.rg);
    cy.get('[data-cy="input-responsible_home_phone"]').type(r.homePhone);
    cy.get('[data-cy="input-responsible_neighborhood"]').type(r.neighborhood);
    cy.get('[data-cy="input-student_school"]').type(s.school);
    cy.get('[data-cy="input-student_grade"]').type(s.grade);
    cy.get('[data-cy="input-student_father_name"]').type(s.fatherName);
    cy.get('[data-cy="input-student_mother_name"]').type(s.motherName);
    cy.get('[data-cy="input-student_phone"]').type(s.phone);
    cy.get('[data-cy="input-student_email"]').type(s.email);
```

- [ ] **Step 4: Cobrir os checkboxes de filiação**

Acrescente ao `describe('Formulário de matrícula', ...)` de `cypress/e2e/enrollment.cy.js`:

```js
  it('desabilita o nome do pai ao marcar que não possui', () => {
    cy.get('[data-cy="checkbox-student_no_father"]').check();
    cy.get('[data-cy="input-student_father_name"]').should('be.disabled');
    cy.get('[data-cy="checkbox-student_no_father"]').uncheck();
    cy.get('[data-cy="input-student_father_name"]').should('not.be.disabled');
  });

  it('recusa o cadastro quando as duas filiações são marcadas como inexistentes', () => {
    cy.get('[data-cy="checkbox-student_no_father"]').check();
    cy.get('[data-cy="checkbox-student_no_mother"]').check();
    cy.get('[data-cy="lgpd-consent-checkbox"]').check();
    cy.get('[data-cy="submit-btn"]').click();
    cy.get('[data-cy="error-student_no_father"]')
      .should('contain', 'pelo menos uma filiação');
  });
```

- [ ] **Step 5: Criar o teste E2E do login**

Crie `cypress/e2e/login.cy.js`:

```js
describe('Login do professor', () => {
  beforeEach(() => {
    cy.visit('/login');
  });

  it('exibe o formulário de login', () => {
    cy.get('[data-cy="login-form"]').should('be.visible');
    cy.get('[data-cy="input-cpf"]').should('be.visible');
    cy.get('[data-cy="input-password"]').should('be.visible');
  });

  it('formata o CPF enquanto é digitado', () => {
    cy.get('[data-cy="input-cpf"]').type('12345678909');
    cy.get('[data-cy="input-cpf"]').should('have.value', '123.456.789-09');
  });

  it('recusa credenciais inválidas', () => {
    cy.get('[data-cy="input-cpf"]').type('12345678909');
    cy.get('[data-cy="input-password"]').type('senha-errada');
    cy.get('[data-cy="login-btn"]').click();
    cy.get('[data-cy="error-cpf"]').should('contain', 'CPF ou senha inválidos');
  });

  it('bloqueia visitante no dashboard', () => {
    cy.visit('/admin/dashboard');
    cy.url().should('include', '/login');
  });
});
```

O teste de login bem-sucedido fica de fora de propósito: dependeria da senha real do ambiente e vazaria credencial para dentro do repositório. Esse caminho já está coberto pelos testes de Feature da Task 3.

- [ ] **Step 6: Rodar o Cypress**

Em um terminal:

```bash
./vendor/bin/sail artisan serve --host=0.0.0.0 --port=8001
```

Em outro:

```bash
npx cypress run
```

Esperado: todas as specs passam. Se algum teste de máscara falhar por timing do Alpine, acrescente `cy.wait(100)` antes da asserção — o `x-effect` só reescreve o input depois que o campo perde o foco.

- [ ] **Step 7: Commit (sugestão para o Paulo)**

```bash
git add cypress cypress.config.js && git commit -m "test: corrigir baseurl e rota do cypress e cobrir campos da ficha"
```

---

### Task 11: Corrigir o bug do campo Data de Nascimento (AJ-02)

O teste de usuário relatou que o campo apaga o conteúdo ao digitar o ano. A causa está no handler Alpine presente nos dois inputs de data:

```blade
x-on:change="const y = parseInt(($el.value || '').split('-')[0]); if (!$el.value || y < 1900 || y > {{ date('Y') }}) $el.value = '';"
```

Um `<input type="date">` do Chrome considera o valor completo assim que os três segmentos têm algum dígito. Ao digitar `2015` no ano, o valor passa por `0002-06-10` — um valor completo, que dispara `change`. O handler lê `y = 2`, conclui que é menor que 1900 e limpa o campo inteiro, no meio da digitação. Pior: `$el.value = ''` mexe no DOM sem avisar o Livewire, então a propriedade do componente continua com o valor antigo enquanto a tela mostra o campo vazio.

Há um segundo defeito no mesmo bloco: o `min` do campo do aluno é `subYears(17)`, enquanto a regra de validação é `after:subYears(18)`. Um aluno de 17 anos e meio passa na validação do servidor mas é recusado pelo navegador.

**Files:**
- Modify: `resources/views/livewire/enrollment-form.blade.php:150-160` e `:272-282`
- Test: `tests/Feature/EnrollmentTest.php`
- Test: `cypress/e2e/enrollment.cy.js`

**Interfaces:**
- Consumes: as regras de `StoreEnrollmentRequest` (Task 5).
- Produces: nenhuma interface nova — só o comportamento corrigido.

- [ ] **Step 1: Escrever os testes que falham**

Acrescente a `tests/Feature/EnrollmentTest.php`:

```php
    public function test_student_with_seventeen_and_a_half_years_is_accepted(): void
    {
        $birthDate = \Illuminate\Support\Carbon::now()->subYears(17)->subMonths(6)->format('Y-m-d');

        $this->fillForm(['student_birth_date' => $birthDate])
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('students', 1);
    }

    public function test_absurd_birth_year_is_rejected_with_a_message(): void
    {
        $this->fillForm(['student_birth_date' => '0002-06-10'])
            ->call('submit')
            ->assertHasErrors('student_birth_date');

        $this->assertDatabaseCount('students', 0);
    }

    public function test_student_older_than_eighteen_is_rejected(): void
    {
        $birthDate = \Illuminate\Support\Carbon::now()->subYears(19)->format('Y-m-d');

        $this->fillForm(['student_birth_date' => $birthDate])
            ->call('submit')
            ->assertHasErrors(['student_birth_date' => 'after']);
    }
```

O primeiro teste é o que expõe o `min` errado; os outros dois provam que recusar data inválida é trabalho da validação do servidor, e não de um handler que apaga o que o usuário digitou.

- [ ] **Step 2: Rodar e conferir**

Run: `./vendor/bin/sail artisan test --filter EnrollmentTest`

Esperado: `test_student_with_seventeen_and_a_half_years_is_accepted` PASSA (a regra do servidor já aceita) e os outros dois PASSAM. Se os três passarem, o defeito é exclusivamente de front-end — os testes de Feature não executam o Alpine. Eles ficam como rede de proteção da regra; a correção do bug é verificada no Cypress, no Step 5.

- [ ] **Step 3: Remover o handler que apaga o campo**

Em `resources/views/livewire/enrollment-form.blade.php`, apague a linha `x-on:change="..."` dos **dois** inputs `type="date"` — o de `responsible_birth_date` e o de `student_birth_date`. Confirme:

```bash
grep -c "y < 1900" resources/views/livewire/enrollment-form.blade.php
```

Esperado: `0`.

O `min` e o `max` continuam guiando o seletor do navegador, e uma data fora da faixa passa a produzir a mensagem em português que já existe em `StoreEnrollmentRequest::enrollmentMessages()`, em vez de sumir com o que foi digitado.

- [ ] **Step 4: Alinhar o `min` do aluno com a regra de validação**

No input de `student_birth_date`, troque

```blade
                                min="{{ \Carbon\Carbon::now()->subYears(17)->format('Y-m-d') }}"
```

por

```blade
                                min="{{ \Carbon\Carbon::now()->subYears(18)->addDay()->format('Y-m-d') }}"
```

Agora o navegador e o servidor concordam: a regra é `after:subYears(18)`, ou seja, a data mais antiga aceita é um dia depois de 18 anos atrás.

- [ ] **Step 5: Cobrir o bug no Cypress**

Acrescente ao `describe('Formulário de matrícula', ...)` de `cypress/e2e/enrollment.cy.js`:

```js
  it('mantém a data de nascimento digitada dígito a dígito', () => {
    cy.get('[data-cy="input-student_birth_date"]').type('2015-06-10');
    cy.get('[data-cy="input-student_birth_date"]').should('have.value', '2015-06-10');
  });

  it('mantém a data de nascimento do responsável digitada dígito a dígito', () => {
    cy.get('[data-cy="input-responsible_birth_date"]').type('1990-01-15');
    cy.get('[data-cy="input-responsible_birth_date"]').should('have.value', '1990-01-15');
  });
```

O `cy.type()` num input de data digita segmento por segmento, que é exatamente a condição que disparava o bug.

- [ ] **Step 6: Conferir no navegador**

Abra `/enrollment` e digite a data de nascimento do aluno pelo teclado, sem usar o seletor. Esperado: o campo mantém o que foi digitado e nada é apagado no meio.

- [ ] **Step 7: Rodar a suíte**

Run: `./vendor/bin/sail artisan test`
Esperado: tudo passa.

- [ ] **Step 8: Commit (sugestão para o Paulo)**

```bash
git add resources/views/livewire/enrollment-form.blade.php tests/Feature/EnrollmentTest.php cypress/e2e/enrollment.cy.js && git commit -m "fix: nao apagar a data de nascimento durante a digitacao do ano"
```

---

### Task 12: Upload da ficha assinada (AJ-06)

Fecha o ciclo do documento: o professor imprime a ficha (Task 8), o responsável assina, e o professor devolve o documento assinado ao sistema. A coluna `students.termo_arquivo` existe desde a migration de maio e nunca foi usada — é ela que passa a guardar o caminho do arquivo.

**Files:**
- Create: `app/Http/Controllers/SignedFichaController.php`
- Create: `database/migrations/2026_08_24_000002_add_termo_arquivo_metadata_to_students.php`
- Modify: `app/Livewire/Admin/Dashboard.php`
- Modify: `resources/views/livewire/admin/dashboard.blade.php`
- Modify: `app/Models/Student.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Admin/SignedFichaUploadTest.php`

**Interfaces:**
- Consumes: `Student`, o grupo de rotas `auth` (Task 4), o componente `Dashboard`.
- Produces:
  - `Dashboard::uploadSignedFicha(string $studentId): void` e `Dashboard::removeSignedFicha(string $studentId): void`.
  - Propriedades públicas `signedFicha` (arquivo) e `uploadTargetId` (string) no `Dashboard`.
  - Rota nomeada `admin.students.ficha-assinada`, em `GET /admin/alunos/{student}/ficha-assinada`.
  - Colunas `students.termo_arquivo_nome` e `students.termo_arquivo_enviado_em`.

- [ ] **Step 1: Escrever o teste que falha**

Crie `tests/Feature/Admin/SignedFichaUploadTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Dashboard;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SignedFichaUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->actingAs(User::factory()->create());
    }

    public function test_upload_stores_the_file_and_marks_the_termo_as_signed(): void
    {
        $student = Student::factory()->create(['termo_status' => 'entregue']);

        Livewire::test(Dashboard::class)
            ->set('uploadTargetId', $student->id)
            ->set('signedFicha', UploadedFile::fake()->create('ficha.pdf', 200, 'application/pdf'))
            ->call('uploadSignedFicha')
            ->assertHasNoErrors();

        $student->refresh();

        $this->assertNotNull($student->termo_arquivo);
        $this->assertSame('ficha.pdf', $student->termo_arquivo_nome);
        $this->assertNotNull($student->termo_arquivo_enviado_em);
        $this->assertSame('assinado', $student->termo_status);
        Storage::disk('local')->assertExists($student->termo_arquivo);
    }

    public function test_upload_accepts_a_photo_of_the_signed_sheet(): void
    {
        $student = Student::factory()->create();

        Livewire::test(Dashboard::class)
            ->set('uploadTargetId', $student->id)
            ->set('signedFicha', UploadedFile::fake()->image('ficha.jpg'))
            ->call('uploadSignedFicha')
            ->assertHasNoErrors();

        $this->assertSame('assinado', $student->fresh()->termo_status);
    }

    public function test_upload_rejects_a_forbidden_file_type(): void
    {
        $student = Student::factory()->create();

        Livewire::test(Dashboard::class)
            ->set('uploadTargetId', $student->id)
            ->set('signedFicha', UploadedFile::fake()->create('malicioso.exe', 10))
            ->call('uploadSignedFicha')
            ->assertHasErrors('signedFicha');

        $this->assertNull($student->fresh()->termo_arquivo);
    }

    public function test_upload_rejects_a_file_over_five_megabytes(): void
    {
        $student = Student::factory()->create();

        Livewire::test(Dashboard::class)
            ->set('uploadTargetId', $student->id)
            ->set('signedFicha', UploadedFile::fake()->create('gigante.pdf', 6000, 'application/pdf'))
            ->call('uploadSignedFicha')
            ->assertHasErrors('signedFicha');

        $this->assertNull($student->fresh()->termo_arquivo);
    }

    public function test_replacing_the_file_deletes_the_previous_one(): void
    {
        $student = Student::factory()->create();

        $component = Livewire::test(Dashboard::class)
            ->set('uploadTargetId', $student->id)
            ->set('signedFicha', UploadedFile::fake()->create('primeira.pdf', 100, 'application/pdf'))
            ->call('uploadSignedFicha');

        $first = $student->fresh()->termo_arquivo;

        $component
            ->set('uploadTargetId', $student->id)
            ->set('signedFicha', UploadedFile::fake()->create('segunda.pdf', 100, 'application/pdf'))
            ->call('uploadSignedFicha');

        $second = $student->fresh()->termo_arquivo;

        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);
    }

    public function test_removing_the_file_clears_the_columns_and_reverts_the_status(): void
    {
        $student = Student::factory()->create();

        $component = Livewire::test(Dashboard::class)
            ->set('uploadTargetId', $student->id)
            ->set('signedFicha', UploadedFile::fake()->create('ficha.pdf', 100, 'application/pdf'))
            ->call('uploadSignedFicha');

        $path = $student->fresh()->termo_arquivo;

        $component->call('removeSignedFicha', $student->id);

        $student->refresh();

        $this->assertNull($student->termo_arquivo);
        $this->assertNull($student->termo_arquivo_nome);
        $this->assertSame('entregue', $student->termo_status);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_authenticated_user_downloads_the_signed_ficha(): void
    {
        $student = Student::factory()->create();

        Livewire::test(Dashboard::class)
            ->set('uploadTargetId', $student->id)
            ->set('signedFicha', UploadedFile::fake()->create('ficha.pdf', 100, 'application/pdf'))
            ->call('uploadSignedFicha');

        $this->get(route('admin.students.ficha-assinada', $student))->assertOk();
    }

    public function test_download_returns_404_when_there_is_no_file(): void
    {
        $student = Student::factory()->create();

        $this->get(route('admin.students.ficha-assinada', $student))->assertNotFound();
    }
}
```

E, na mesma pasta, um teste de acesso sem sessão — ele não pode herdar o `actingAs` do `setUp` acima, por isso vai em classe própria. Acrescente ao final de `tests/Feature/Admin/DashboardTest.php`:

```php
    public function test_guest_cannot_download_a_signed_ficha(): void
    {
        $student = Student::factory()->create();

        $this->get(route('admin.students.ficha-assinada', $student))
            ->assertRedirect(route('login'));
    }
```

- [ ] **Step 2: Rodar e confirmar a falha**

Run: `./vendor/bin/sail artisan test --filter SignedFichaUploadTest`
Esperado: FALHA com `Property [$uploadTargetId] not found on component`.

- [ ] **Step 3: Criar a migration dos metadados**

Run: `./vendor/bin/sail artisan make:migration add_termo_arquivo_metadata_to_students`

Renomeie para `2026_08_24_000002_add_termo_arquivo_metadata_to_students.php` e substitua o conteúdo:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('termo_arquivo_nome')->nullable()->after('termo_arquivo');
            $table->timestamp('termo_arquivo_enviado_em')->nullable()->after('termo_arquivo_nome');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['termo_arquivo_nome', 'termo_arquivo_enviado_em']);
        });
    }
};
```

O nome original do arquivo é guardado à parte porque o caminho em disco é normalizado; sem ele, o download entregaria um nome sem sentido para o professor.

- [ ] **Step 4: Declarar as colunas no model**

Em `app/Models/Student.php`, acrescente ao `$fillable`, depois de `'termo_arquivo'`:

```php
        'termo_arquivo_nome',
        'termo_arquivo_enviado_em',
```

e ao `casts()`:

```php
            'termo_arquivo_enviado_em' => 'datetime',
```

- [ ] **Step 5: Implementar o upload no componente**

Em `app/Livewire/Admin/Dashboard.php`, substitua o arquivo inteiro por:

```php
<?php

namespace App\Livewire\Admin;

use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Dashboard extends Component
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $signedFicha = null;

    public string $uploadTargetId = '';

    public function updateTermoStatus(string $studentId, string $status): void
    {
        if (! in_array($status, ['pendente', 'entregue', 'assinado'])) {
            return;
        }

        Student::findOrFail($studentId)->update(['termo_status' => $status]);
    }

    public function uploadSignedFicha(): void
    {
        $this->validate([
            'uploadTargetId' => ['required', 'uuid'],
            'signedFicha' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'signedFicha.required' => 'Escolha o arquivo da ficha assinada.',
            'signedFicha.mimes' => 'A ficha assinada deve ser um PDF ou uma imagem (JPG ou PNG).',
            'signedFicha.max' => 'O arquivo não pode passar de 5 MB.',
        ]);

        $student = Student::findOrFail($this->uploadTargetId);

        if ($student->termo_arquivo) {
            Storage::disk('local')->delete($student->termo_arquivo);
        }

        $path = $this->signedFicha->storeAs(
            'fichas-assinadas',
            $student->id.'-'.now()->timestamp.'.'.$this->signedFicha->getClientOriginalExtension(),
            'local',
        );

        $student->update([
            'termo_arquivo' => $path,
            'termo_arquivo_nome' => $this->signedFicha->getClientOriginalName(),
            'termo_arquivo_enviado_em' => now(),
            'termo_status' => 'assinado',
        ]);

        $this->reset('signedFicha', 'uploadTargetId');
    }

    public function removeSignedFicha(string $studentId): void
    {
        $student = Student::findOrFail($studentId);

        if ($student->termo_arquivo) {
            Storage::disk('local')->delete($student->termo_arquivo);
        }

        $student->update([
            'termo_arquivo' => null,
            'termo_arquivo_nome' => null,
            'termo_arquivo_enviado_em' => null,
            'termo_status' => 'entregue',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.dashboard', [
            'students' => Student::with('responsible')->orderBy('created_at')->get(),
        ])->layout('layouts.admin');
    }
}
```

O disco é o `local`, e não o `public`: a ficha assinada tem CPF, RG e endereço de menor de idade e não pode ficar acessível por URL direta. O download passa pela rota autenticada do próximo passo.

- [ ] **Step 6: Escrever o controller de download**

Crie `app/Http/Controllers/SignedFichaController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SignedFichaController extends Controller
{
    public function __invoke(Student $student): Response
    {
        abort_if(
            ! $student->termo_arquivo || ! Storage::disk('local')->exists($student->termo_arquivo),
            404,
        );

        return Storage::disk('local')->download(
            $student->termo_arquivo,
            $student->termo_arquivo_nome ?? 'ficha-assinada',
        );
    }
}
```

- [ ] **Step 7: Registrar a rota**

Em `routes/web.php`, dentro do grupo `auth`, logo depois da rota da ficha em branco:

```php
    Route::get('/admin/alunos/{student}/ficha-assinada', SignedFichaController::class)
        ->name('admin.students.ficha-assinada');
```

e o import:

```php
use App\Http\Controllers\SignedFichaController;
```

- [ ] **Step 8: Rodar o teste**

Run: `./vendor/bin/sail artisan test --filter SignedFichaUploadTest`
Esperado: 8 testes PASSAM.

- [ ] **Step 9: Acrescentar a interface ao modal**

Em `resources/views/livewire/admin/dashboard.blade.php`, acrescente ao `$modalData` as duas chaves do arquivo:

```blade
            'termo_arquivo'      => (bool) $s->termo_arquivo,
            'termo_arquivo_nome' => $s->termo_arquivo_nome,
```

E, ao final do modal, depois da seção "Estudante", insira a seção de anexo:

```blade
                {{-- Ficha assinada --}}
                <section>
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-neutral-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-neutral-600"></span>
                        Ficha assinada
                    </h3>

                    <template x-if="s.termo_arquivo">
                        <div class="flex flex-wrap items-center gap-3">
                            <a
                                :href="'/admin/alunos/' + s.id + '/ficha-assinada'"
                                target="_blank"
                                data-cy="ficha-assinada-link"
                                class="inline-block bg-white text-black font-bold px-5 py-2 rounded-lg hover:bg-neutral-200 transition"
                            >
                                Baixar ficha assinada
                            </a>
                            <span class="text-sm text-neutral-500" x-text="s.termo_arquivo_nome"></span>
                            <button
                                type="button"
                                @click="$wire.removeSignedFicha(s.id).then(() => close())"
                                data-cy="ficha-assinada-remove"
                                class="text-sm text-red-400 hover:text-red-300 transition"
                            >
                                Remover
                            </button>
                        </div>
                    </template>

                    <template x-if="! s.termo_arquivo">
                        <div>
                            <p class="text-sm text-neutral-500 mb-3">
                                Anexe o PDF ou a foto da ficha que o responsável assinou.
                                O status do termo passa a "assinado" automaticamente.
                            </p>
                            <input
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                data-cy="ficha-assinada-input"
                                @change="$wire.set('uploadTargetId', s.id)"
                                wire:model="signedFicha"
                                class="block w-full text-sm text-neutral-400 file:mr-4 file:rounded-lg file:border-0
                                       file:bg-neutral-800 file:px-4 file:py-2 file:text-sm file:font-semibold
                                       file:text-neutral-200 hover:file:bg-neutral-700"
                            >
                            <div wire:loading wire:target="signedFicha" class="text-sm text-neutral-500 mt-2">
                                Enviando arquivo...
                            </div>
                            <button
                                type="button"
                                @click="$wire.uploadSignedFicha().then(() => close())"
                                data-cy="ficha-assinada-submit"
                                class="mt-3 bg-white text-black font-bold px-5 py-2 rounded-lg hover:bg-neutral-200 transition"
                            >
                                Salvar ficha assinada
                            </button>
                            @error('signedFicha')
                                <p class="text-red-400 text-sm mt-2" data-cy="error-signedFicha">{{ $message }}</p>
                            @enderror
                        </div>
                    </template>
                </section>
```

O `@change` grava o `uploadTargetId` antes de o upload terminar, porque o modal é client-side e o componente não sabe qual aluno está aberto.

- [ ] **Step 10: Rodar os testes do dashboard**

Run: `./vendor/bin/sail artisan test --filter Admin`
Esperado: todos PASSAM.

- [ ] **Step 11: Conferir no navegador**

No dashboard, abra o modal de um aluno, anexe um PDF qualquer e salve. Esperado: o status vira "Assinado", o botão "Baixar ficha assinada" aparece, o download entrega o arquivo com o nome original, e "Remover" devolve o status para "Entregue".

- [ ] **Step 12: Ignorar os arquivos enviados no git**

Confirme que `storage/app` já está ignorado:

```bash
git check-ignore -v storage/app/private/fichas-assinadas 2>/dev/null || echo "ATENÇÃO: acrescente /storage/app/private ao .gitignore"
```

- [ ] **Step 13: Lint e commit (sugestão para o Paulo)**

```bash
./vendor/bin/sail exec laravel.test vendor/bin/pint
```

```bash
git add app database routes/web.php resources/views/livewire/admin/dashboard.blade.php tests/Feature/Admin && git commit -m "feat: upload e download da ficha assinada na area do professor"
```

---

### Task 13: Documentação

**Files:**
- Modify: `CLAUDE.md`
- Modify: `README.md`

**Interfaces:**
- Consumes: tudo o que foi construído.
- Produces: documentação que descreve o repositório como ele realmente é.

- [ ] **Step 1: Corrigir o que o CLAUDE.md afirma sobre os testes**

Na seção "Databases", o texto atual diz que os testes usam `.env.testing` + `phpunit.xml`. Isso passou a ser verdade só depois da Task 0 — antes a suíte ia para o Neon. Substitua o bloco por:

```markdown
### Databases
- **Local dev** (`.env`): Neon.tech hosted PostgreSQL (`sa-east-1`)
- **Tests** (`.env.testing`): SQLite at `database/testing.sqlite`. O arquivo é
  ignorado pelo git; crie-o com `touch database/testing.sqlite` na primeira vez.

Session, cache, and queue all use the `database` driver, so the migrations must be run before the app works.
```

- [ ] **Step 2: Registrar a exceção do controller**

Na seção "Request flow", depois do parágrafo existente, acrescente:

```markdown
A única exceção é `StudentFichaController` (invocável), que serve o download da
ficha em PDF em `GET /admin/alunos/{student}/ficha`. Downloads binários não são
páginas Livewire: uma URL estável abre em nova aba, sobrevive a refresh e é
testável com `$this->get(...)`.
```

- [ ] **Step 3: Documentar a autenticação**

Acrescente uma seção nova ao `CLAUDE.md`, depois de "Databases":

```markdown
### Autenticação
O login é por **CPF**, não e-mail — `users.cpf` (11 dígitos, único) e
`Auth::attempt(['cpf' => ..., 'password' => ...])`. Existe um único tipo de
usuário, o professor; não há coluna `role` nem tela de registro.

O usuário é criado por `ProfessorSeeder`, que lê `config/professor.php` (e este,
as variáveis `PROFESSOR_*` do `.env`). Rotas sob `middleware('auth')`:
`/admin/dashboard` e a ficha em PDF.
```

- [ ] **Step 4: Documentar a ficha no README**

Acrescente ao `README.md` uma seção:

```markdown
## Área do Professor

O acesso ao dashboard exige login em `/login`, com **CPF e senha**.

Crie o usuário professor com:

    ./vendor/bin/sail artisan db:seed --class=ProfessorSeeder

As credenciais vêm das variáveis `PROFESSOR_CPF`, `PROFESSOR_PASSWORD`,
`PROFESSOR_NAME` e `PROFESSOR_EMAIL` do `.env` (veja `.env.example`).
Os valores padrão — CPF `12345678909`, senha `heroisdotatame` — servem
**apenas para desenvolvimento**. Troque a senha no `.env` e rode o seeder de
novo antes de qualquer uso real; o seeder é idempotente e atualiza o usuário
existente.

## Ficha de cadastro de atleta

No dashboard, o botão **Gerar ficha** de cada aluno baixa em PDF a Ficha de
Cadastro de Atleta no formato exigido pela Secretaria Municipal de Esportes e
Recreação de Prudentópolis, já preenchida com os dados do cadastro. Só restam
as duas assinaturas.

O PDF é gerado sob demanda pelo dompdf e nunca gravado em disco: corrigir um
dado do aluno e reimprimir sempre produz a versão atual.
```

- [ ] **Step 5: Commit (sugestão para o Paulo)**

```bash
git add CLAUDE.md README.md && git commit -m "docs: documentar login por cpf e geracao da ficha em pdf"
```

---

### Task 14: Relatório da sprint

O Paulo pediu um documento fechando o trabalho, com seis seções: Decisões, Dificuldades, Processos, Problemas, Soluções e Resultados.

**Files:**
- Create: `docs/RELATORIO_SPRINT_3.md`

**Interfaces:**
- Consumes: o histórico real da execução — nada aqui é inventado.
- Produces: o relatório, e um Artifact publicado a partir dele.

- [ ] **Step 1: Escrever o relatório**

O documento cobre, nesta ordem:

- **Decisões** — escopo fechado com o Paulo a partir do backlog de ajustes: entram AJ-01, AJ-02, AJ-06 e a geração do termo; ficam de fora AJ-04, AJ-07 e AJ-08. dompdf em vez de print-to-PDF do navegador; login por CPF em vez de e-mail; entrega da ficha pelo professor em vez de download pelo responsável; colunas `nullable` com obrigatoriedade só na validação; checkbox de filiação inexistente em vez de campo opcional; PDF gerado sob demanda em vez de armazenado; controller invocável como exceção deliberada ao padrão Livewire do projeto.
- **Dificuldades** — os campos que a ficha oficial exige e o schema não tinha; migrar uma tabela que já tem linhas em produção; o `required_unless` do Laravel comparando booleano do Livewire com string.
- **Processos** — brainstorming → spec → plano → TDD tarefa a tarefa, com teste vermelho antes de cada implementação, `pint` e commit ao final de cada uma.
- **Problemas** — a suíte rodando contra o Neon em vez de SQLite; `tests/Unit` ausente quebrando `artisan test`; Cypress apontando para uma URL morta de Codespaces e para a rota `/matricula`, que não existe; `nullable` do Laravel não ignorando string vazia nos campos mascarados; o clique no link da ficha disparando o modal da linha; o campo de data se apagando durante a digitação do ano (AJ-02), com o `min` do navegador discordando da regra do servidor.
- **Soluções** — o que foi feito para cada problema acima, com o arquivo onde a correção mora.
- **Resultados** — número de testes ao final; o que passou a existir (login por CPF, ficha em PDF, campos novos, correção do AJ-02, upload da ficha assinada do AJ-06); e o que ficou de fora por decisão do Paulo (AJ-04 dados reais do rodapé, AJ-07 responsividade da tabela no mobile, AJ-08 redirect pós-matrícula), além dos não-objetivos do spec (logos oficiais da SMER, envio por e-mail, assinatura digital).

Cada item precisa do fato concreto: caminho de arquivo, número de teste, comando. Nada de "melhoramos a qualidade do código".

- [ ] **Step 2: Contar os testes para a seção de Resultados**

Run: `./vendor/bin/sail artisan test`

Anote o total exibido na linha `Tests:` e use esse número — não estime.

- [ ] **Step 3: Publicar como Artifact**

Publique `docs/RELATORIO_SPRINT_3.md` com a ferramenta Artifact, carregando antes a skill `artifact-design`, e entregue o link ao Paulo.

- [ ] **Step 4: Commit (sugestão para o Paulo)**

```bash
git add docs/RELATORIO_SPRINT_3.md && git commit -m "docs: relatorio da sprint de login e ficha em pdf"
```

---

## Verificação final

Depois da Task 14, antes de declarar o trabalho pronto:

- [ ] `./vendor/bin/sail artisan test` — suíte inteira verde, sem `--filter`
- [ ] `./vendor/bin/sail exec laravel.test vendor/bin/pint --test` — sem violações de estilo
- [ ] `npx cypress run` com `artisan serve --port=8001` no ar — specs verdes
- [ ] Login manual em `/login`, dashboard carrega, "Gerar ficha" baixa um PDF legível
- [ ] Visitante em `/admin/dashboard` cai em `/login`
- [ ] Uma matrícula completa pelo formulário público, ponta a ponta, gera uma ficha correta
- [ ] AJ-02: digitar a data de nascimento pelo teclado, dígito a dígito, sem o campo se apagar
- [ ] AJ-06: anexar a ficha assinada, baixá-la de volta com o nome original e removê-la
