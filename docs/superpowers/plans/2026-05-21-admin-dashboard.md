# Admin Dashboard Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Create the `/admin/dashboard` restricted area listing all enrolled students with guardian data and a toggleable term status badge, plus rename `/matricula` to `/enrollment`.

**Architecture:** Follows the existing TALL stack pattern — a Livewire full-page component (`Admin\Dashboard`) handles both rendering and the status update action. A dedicated admin layout wraps the page. Laravel's `auth` middleware on the route controls access; login implementation is out of scope for this delivery.

**Tech Stack:** Laravel 13, Livewire 4, Tailwind CSS, PostgreSQL (dev) / SQLite (tests)

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `database/migrations/TIMESTAMP_add_termo_columns_to_students_table.php` | Create | Adds `termo_status` (default `'pendente'`) and `termo_arquivo` (nullable) to `students` |
| `app/Models/Student.php` | Modify | Add new columns to `$fillable` |
| `database/factories/StudentFactory.php` | Modify | Include `termo_status` in factory definition |
| `tests/Feature/StudentTermoStatusTest.php` | Create | Verifies migration defaults and model update |
| `routes/web.php` | Modify | Rename `/matricula` → `/enrollment`, add `/admin/dashboard` with `auth`, add `/logout` |
| `tests/Feature/EnrollmentTest.php` | Modify | Add tests for renamed route |
| `app/Livewire/Admin/Dashboard.php` | Create | Loads students eagerly, exposes `updateTermoStatus` action |
| `resources/views/layouts/admin.blade.php` | Create | Dark header for admin area |
| `resources/views/livewire/admin/dashboard.blade.php` | Create | Table with color-coded status badges and `<select>` per row |
| `resources/views/layouts/app.blade.php` | Modify | Add discreet admin link in the page footer |
| `tests/Feature/Admin/DashboardTest.php` | Create | Component behavior + route-level auth tests |

---

## Task 1: Add termo columns to students table

**Files:**
- Create: `tests/Feature/StudentTermoStatusTest.php`
- Create: `database/migrations/TIMESTAMP_add_termo_columns_to_students_table.php`
- Modify: `app/Models/Student.php`
- Modify: `database/factories/StudentFactory.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/StudentTermoStatusTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentTermoStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_defaults_to_pendente_termo_status(): void
    {
        $student = Student::factory()->create();

        $this->assertEquals('pendente', $student->fresh()->termo_status);
    }

    public function test_termo_arquivo_defaults_to_null(): void
    {
        $student = Student::factory()->create();

        $this->assertNull($student->fresh()->termo_arquivo);
    }

    public function test_termo_status_can_be_updated(): void
    {
        $student = Student::factory()->create(['termo_status' => 'pendente']);

        $student->update(['termo_status' => 'entregue']);

        $this->assertEquals('entregue', $student->fresh()->termo_status);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

```bash
./vendor/bin/sail artisan test --filter StudentTermoStatusTest
```

Expected: FAIL — column `termo_status` does not exist

- [ ] **Step 3: Create the migration**

```bash
./vendor/bin/sail artisan make:migration add_termo_columns_to_students_table
```

Open the generated file in `database/migrations/` and replace its content:

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
            $table->string('termo_status')->default('pendente')->after('modalidade');
            $table->string('termo_arquivo')->nullable()->after('termo_status');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['termo_status', 'termo_arquivo']);
        });
    }
};
```

- [ ] **Step 4: Update Student model**

In `app/Models/Student.php`, update `$fillable` to include the two new columns:

```php
protected $fillable = [
    'responsible_id',
    'name',
    'cpf',
    'rg',
    'birth_date',
    'modalidade',
    'termo_status',
    'termo_arquivo',
];
```

- [ ] **Step 5: Update StudentFactory**

In `database/factories/StudentFactory.php`, add `termo_status` to `definition()`:

```php
public function definition(): array
{
    return [
        'responsible_id' => Responsible::factory(),
        'name'           => $this->faker->name(),
        'cpf'            => $this->faker->unique()->numerify('###########'),
        'rg'             => $this->faker->optional(0.7)->numerify('#########'),
        'birth_date'     => $this->faker->dateTimeBetween('-16 years', '-9 years')->format('Y-m-d'),
        'modalidade'     => $this->faker->randomElement(['Jiu Jitsu', 'Muay Thai', 'Taekwondo', 'Boxe']),
        'termo_status'   => 'pendente',
    ];
}
```

- [ ] **Step 6: Run test to verify it passes**

```bash
./vendor/bin/sail artisan test --filter StudentTermoStatusTest
```

Expected: 3 tests PASS

- [ ] **Step 7: Commit**

```bash
git add database/migrations/ app/Models/Student.php database/factories/StudentFactory.php tests/Feature/StudentTermoStatusTest.php
git commit -m "feat: add termo_status and termo_arquivo columns to students table"
```

---

## Task 2: Rename enrollment route URL from /matricula to /enrollment

**Files:**
- Modify: `routes/web.php`
- Modify: `tests/Feature/EnrollmentTest.php`

- [ ] **Step 1: Add route tests**

At the end of the test class body in `tests/Feature/EnrollmentTest.php`, add:

```php
public function test_enrollment_route_is_accessible_at_english_url(): void
{
    $this->get('/enrollment')->assertStatus(200);
}

public function test_old_matricula_url_no_longer_exists(): void
{
    $this->get('/matricula')->assertStatus(404);
}
```

- [ ] **Step 2: Run new tests to verify they fail**

```bash
./vendor/bin/sail artisan test --filter "test_enrollment_route_is_accessible_at_english_url|test_old_matricula_url_no_longer_exists"
```

Expected: first test FAILS (currently 404), second test FAILS (currently 200)

- [ ] **Step 3: Update routes/web.php**

Change `/matricula` to `/enrollment`:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Home;
use App\Livewire\EnrollmentForm;

Route::get('/', Home::class)->name('home');
Route::get('/enrollment', EnrollmentForm::class)->name('enrollment');
```

- [ ] **Step 4: Run all enrollment tests to verify nothing broke**

```bash
./vendor/bin/sail artisan test --filter EnrollmentTest
```

Expected: all tests PASS (all existing + 2 new)

- [ ] **Step 5: Commit**

```bash
git add routes/web.php tests/Feature/EnrollmentTest.php
git commit -m "feat: rename enrollment route URL from /matricula to /enrollment"
```

---

## Task 3: Create admin layout

**Files:**
- Create: `resources/views/layouts/admin.blade.php`

- [ ] **Step 1: Create the layout file**

Create `resources/views/layouts/admin.blade.php`:

```html
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Heróis do Tatame</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-black text-white antialiased font-sans min-h-screen">

    <header class="sticky top-0 z-50 w-full bg-black/80 backdrop-blur-md border-b border-neutral-900">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                <span class="text-xl font-bold tracking-widest uppercase">Heróis do Tatame</span>
                <span class="text-xs bg-neutral-800 text-neutral-400 border border-neutral-700 px-2 py-0.5 rounded-full uppercase tracking-widest">Admin</span>
            </div>
            @if(Route::has('logout'))
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-neutral-400 hover:text-white transition">Sair</button>
            </form>
            @endif
        </div>
    </header>

    <main class="container mx-auto px-6 py-10">
        {{ $slot }}
    </main>

</body>
</html>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/layouts/admin.blade.php
git commit -m "feat: add admin layout"
```

---

## Task 4: Create Admin\Dashboard Livewire component and view

**Files:**
- Create: `tests/Feature/Admin/DashboardTest.php`
- Create: `app/Livewire/Admin/Dashboard.php`
- Create: `resources/views/livewire/admin/dashboard.blade.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/Admin/DashboardTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Dashboard;
use App\Models\Responsible;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_for_authenticated_user(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Dashboard::class)->assertStatus(200);
    }

    public function test_dashboard_shows_student_name(): void
    {
        $this->actingAs(User::factory()->create());
        Student::factory()->create(['name' => 'João da Silva']);

        Livewire::test(Dashboard::class)->assertSee('João da Silva');
    }

    public function test_dashboard_shows_responsible_name_and_phone(): void
    {
        $this->actingAs(User::factory()->create());
        $responsible = Responsible::factory()->create([
            'name'         => 'Maria Souza',
            'phone_number' => '11987654321',
        ]);
        Student::factory()->create(['responsible_id' => $responsible->id]);

        Livewire::test(Dashboard::class)
            ->assertSee('Maria Souza')
            ->assertSee('11987654321');
    }

    public function test_dashboard_shows_all_students(): void
    {
        $this->actingAs(User::factory()->create());
        Student::factory()->create(['name' => 'Aluno Um']);
        Student::factory()->create(['name' => 'Aluno Dois']);

        Livewire::test(Dashboard::class)
            ->assertSee('Aluno Um')
            ->assertSee('Aluno Dois');
    }

    public function test_dashboard_shows_termo_status(): void
    {
        $this->actingAs(User::factory()->create());
        Student::factory()->create(['termo_status' => 'pendente']);

        Livewire::test(Dashboard::class)->assertSee('Pendente');
    }

    public function test_update_termo_status_to_entregue(): void
    {
        $this->actingAs(User::factory()->create());
        $student = Student::factory()->create(['termo_status' => 'pendente']);

        Livewire::test(Dashboard::class)
            ->call('updateTermoStatus', $student->id, 'entregue')
            ->assertHasNoErrors();

        $this->assertEquals('entregue', $student->fresh()->termo_status);
    }

    public function test_update_termo_status_to_assinado(): void
    {
        $this->actingAs(User::factory()->create());
        $student = Student::factory()->create(['termo_status' => 'entregue']);

        Livewire::test(Dashboard::class)
            ->call('updateTermoStatus', $student->id, 'assinado');

        $this->assertEquals('assinado', $student->fresh()->termo_status);
    }

    public function test_invalid_termo_status_is_ignored(): void
    {
        $this->actingAs(User::factory()->create());
        $student = Student::factory()->create(['termo_status' => 'pendente']);

        Livewire::test(Dashboard::class)
            ->call('updateTermoStatus', $student->id, 'invalido');

        $this->assertEquals('pendente', $student->fresh()->termo_status);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/sail artisan test --filter DashboardTest
```

Expected: FAIL — class `App\Livewire\Admin\Dashboard` not found

- [ ] **Step 3: Create the Livewire component class**

Create `app/Livewire/Admin/Dashboard.php`:

```php
<?php

namespace App\Livewire\Admin;

use App\Models\Student;
use Livewire\Component;

class Dashboard extends Component
{
    public function updateTermoStatus(string $studentId, string $status): void
    {
        if (!in_array($status, ['pendente', 'entregue', 'assinado'])) {
            return;
        }

        Student::findOrFail($studentId)->update(['termo_status' => $status]);
    }

    public function render()
    {
        return view('livewire.admin.dashboard', [
            'students' => Student::with('responsible')->get(),
        ])->layout('layouts.admin');
    }
}
```

- [ ] **Step 4: Create the Blade view**

Create `resources/views/livewire/admin/dashboard.blade.php`:

```html
<div>
    <div class="mb-8">
        <h1 class="text-3xl font-bold uppercase tracking-wide text-white">Alunos Cadastrados</h1>
        <p class="text-neutral-500 mt-1 text-sm">
            {{ $students->count() }} {{ $students->count() === 1 ? 'aluno cadastrado' : 'alunos cadastrados' }}
        </p>
    </div>

    @if($students->isEmpty())
        <div class="text-center py-20 text-neutral-600">
            <p class="text-lg">Nenhum aluno cadastrado ainda.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-neutral-800">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-neutral-900 text-neutral-400 uppercase text-xs tracking-wider">
                        <th class="px-6 py-4 text-left">Responsável</th>
                        <th class="px-6 py-4 text-left">Contato</th>
                        <th class="px-6 py-4 text-left">Aluno</th>
                        <th class="px-6 py-4 text-left">Status do Termo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-900">
                    @foreach($students as $student)
                        <tr class="bg-neutral-950 hover:bg-neutral-900 transition-colors duration-150">
                            <td class="px-6 py-4 text-neutral-200">{{ $student->responsible->name }}</td>
                            <td class="px-6 py-4 text-neutral-400 font-mono">{{ $student->responsible->phone_number }}</td>
                            <td class="px-6 py-4 text-neutral-200">{{ $student->name }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @php
                                        $badgeClass = match($student->termo_status) {
                                            'entregue' => 'bg-yellow-950 text-yellow-400 border-yellow-800',
                                            'assinado' => 'bg-green-950 text-green-400 border-green-800',
                                            default    => 'bg-red-950 text-red-400 border-red-800',
                                        };
                                    @endphp
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold border {{ $badgeClass }}">
                                        {{ ucfirst($student->termo_status) }}
                                    </span>
                                    <select
                                        wire:change="updateTermoStatus('{{ $student->id }}', $event.target.value)"
                                        class="bg-neutral-800 border border-neutral-700 text-neutral-300 text-xs rounded-md px-2 py-1 focus:outline-none focus:ring-1 focus:ring-neutral-600"
                                    >
                                        <option value="pendente" @selected($student->termo_status === 'pendente')>Pendente</option>
                                        <option value="entregue" @selected($student->termo_status === 'entregue')>Entregue</option>
                                        <option value="assinado" @selected($student->termo_status === 'assinado')>Assinado</option>
                                    </select>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
```

- [ ] **Step 5: Run tests to verify they pass**

```bash
./vendor/bin/sail artisan test --filter DashboardTest
```

Expected: 8 tests PASS

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Admin/Dashboard.php resources/views/livewire/admin/dashboard.blade.php tests/Feature/Admin/DashboardTest.php
git commit -m "feat: add admin dashboard component with termo status management"
```

---

## Task 5: Register admin route and logout with auth middleware

**Files:**
- Modify: `routes/web.php`
- Modify: `tests/Feature/Admin/DashboardTest.php`

- [ ] **Step 1: Add route-level auth tests**

Add these two tests to `tests/Feature/Admin/DashboardTest.php`:

```php
public function test_unauthenticated_user_is_redirected_from_admin_dashboard(): void
{
    $this->get('/admin/dashboard')->assertRedirect('/login');
}

public function test_authenticated_user_can_reach_admin_dashboard(): void
{
    $this->actingAs(User::factory()->create());

    $this->get('/admin/dashboard')->assertStatus(200);
}
```

- [ ] **Step 2: Run new tests to verify they fail**

```bash
./vendor/bin/sail artisan test --filter "test_unauthenticated_user_is_redirected|test_authenticated_user_can_reach_admin_dashboard"
```

Expected: FAIL — route `admin.dashboard` not found (404 on both)

- [ ] **Step 3: Update routes/web.php**

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Home;
use App\Livewire\EnrollmentForm;
use App\Livewire\Admin\Dashboard;

Route::get('/', Home::class)->name('home');
Route::get('/enrollment', EnrollmentForm::class)->name('enrollment');

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

- [ ] **Step 4: Run all tests**

```bash
./vendor/bin/sail artisan test
```

Expected: all tests PASS

- [ ] **Step 5: Commit**

```bash
git add routes/web.php tests/Feature/Admin/DashboardTest.php
git commit -m "feat: register admin dashboard and logout routes with auth middleware"
```

---

## Done

At this point:
- `/enrollment` serves the enrollment form (renamed from `/matricula`)
- `/admin/dashboard` lists all enrolled students with responsible name, contact, student name, and a color-coded term status badge + select; protected by `auth` middleware
- Changing the status dropdown triggers `updateTermoStatus` via Livewire and persists instantly without page reload
- `termo_arquivo` column exists in `students` — prepared for PDF upload in a future sprint
- Login screen and session management are out of scope for this delivery
