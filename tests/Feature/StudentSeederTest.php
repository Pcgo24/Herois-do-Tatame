<?php

namespace Tests\Feature;

use App\Models\Student;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentSeederTest extends TestCase
{
    use RefreshDatabase;

    // O DatabaseSeeder desliga os eventos de modelo, então a data de matrícula
    // não pode depender do hook "creating" para os alunos de demonstração.
    public function test_every_seeded_student_has_an_enrollment_date(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThan(150, Student::count());
        $this->assertSame(0, Student::whereNull('matricula_em')->count());
    }

    public function test_the_three_demo_students_cover_every_situacao(): void
    {
        $this->seed(DatabaseSeeder::class);

        $situacoes = Student::whereIn('cpf', ['39053344705', '11144477735', '22233344456'])
            ->get()->map->situacaoMatricula()->sort()->values()->all();

        $this->assertSame(['ok', 'vencendo', 'vencida'], $situacoes);
    }
}
