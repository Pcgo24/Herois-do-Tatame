<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_one_professor_per_modalidade(): void
    {
        $this->seed(DemoSeeder::class);

        $usernames = User::professors()->orderBy('username')->pluck('username')->all();

        $this->assertSame(['prof_boxe', 'prof_jiujitsu', 'prof_muaythai', 'prof_taekwondo'], $usernames);
        $this->assertTrue(Hash::check('heroisdotatame', User::where('username', 'prof_boxe')->first()->password));
    }

    public function test_it_creates_150_students_spread_across_every_situacao(): void
    {
        $this->seed(DemoSeeder::class);

        $this->assertSame(150, Student::count());

        $situacoes = Student::all()->map->situacaoMatricula()->unique()->sort()->values()->all();
        $this->assertSame(['ok', 'vencendo', 'vencida'], $situacoes);
    }

    public function test_it_varies_the_termo_status(): void
    {
        $this->seed(DemoSeeder::class);

        $porStatus = Student::query()->get()->countBy('termo_status')->all();

        $this->assertSame(['assinado' => 37, 'entregue' => 37, 'pendente' => 76], collect($porStatus)->sortKeys()->all());
    }

    public function test_running_it_twice_does_not_duplicate(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertSame(150, Student::count());
        $this->assertSame(4, User::professors()->count());
    }

    public function test_it_refuses_to_run_in_production(): void
    {
        app()->detectEnvironment(fn () => 'production');

        // Direto, sem o db:seed: em produção o comando pediria confirmação.
        app(DemoSeeder::class)->run();

        $this->assertSame(0, Student::count());
        $this->assertSame(0, User::count());
    }
}
