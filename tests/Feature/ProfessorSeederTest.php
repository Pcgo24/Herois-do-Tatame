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

    public function test_seeder_creates_professor_with_default_username(): void
    {
        $this->seed(ProfessorSeeder::class);

        $professor = User::where('username', 'professor')->first();

        $this->assertNotNull($professor);
        $this->assertTrue(Hash::check('heroisdotatame', $professor->password));
    }

    public function test_seeder_respects_environment_variables(): void
    {
        config()->set('professor.username', 'alisson_antunes');
        config()->set('professor.password', 'senha-secreta');
        config()->set('professor.name', 'Alisson Antunes');

        $this->seed(ProfessorSeeder::class);

        $professor = User::where('username', 'alisson_antunes')->first();

        $this->assertNotNull($professor);
        $this->assertSame('Alisson Antunes', $professor->name);
        $this->assertTrue(Hash::check('senha-secreta', $professor->password));
    }

    // O seeder roda a cada deploy. Ele é só o bootstrap do primeiro usuário:
    // depois disso, quem manda em senhas e usuários é o painel, não o .env.
    public function test_seeder_does_nothing_when_a_user_already_exists(): void
    {
        User::factory()->create(['username' => 'alisson_antunes']);

        config()->set('professor.username', 'outro_professor');
        $this->seed(ProfessorSeeder::class);

        $this->assertSame(1, User::count());
        $this->assertNull(User::where('username', 'outro_professor')->first());
    }

    public function test_seeder_does_not_reset_the_password_on_redeploy(): void
    {
        $this->seed(ProfessorSeeder::class);

        $professor = User::where('username', 'professor')->first();
        $professor->update(['password' => Hash::make('senha-trocada-no-painel')]);

        $this->seed(ProfessorSeeder::class);

        $this->assertTrue(Hash::check('senha-trocada-no-painel', $professor->fresh()->password));
    }

    // Um usuário removido ainda ocupa a tabela: o bootstrap não pode ressuscitar
    // um cadastro que o painel apagou, nem criar um segundo com o mesmo nome.
    public function test_seeder_does_nothing_when_the_only_user_was_soft_deleted(): void
    {
        $user = User::factory()->create(['username' => 'professor']);
        $user->delete();

        $this->seed(ProfessorSeeder::class);

        $this->assertSame(1, User::withTrashed()->count());
        $this->assertNotNull(User::withTrashed()->find($user->id)->deleted_at);
    }

    public function test_user_factory_generates_username(): void
    {
        $user = User::factory()->create();

        $this->assertMatchesRegularExpression('/^[a-z0-9_.]{3,30}$/', $user->username);
    }
}
