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
