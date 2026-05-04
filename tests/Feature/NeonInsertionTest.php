<?php

namespace Tests\Feature;

use App\Models\Responsible;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('neon')]
class NeonInsertionTest extends TestCase
{
    private array $insertedResponsibleCpfs = [];
    private array $insertedStudentCpfs = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'pgsql']);
    }

    protected function tearDown(): void
    {
        if (!empty($this->insertedStudentCpfs)) {
            Student::withTrashed()
                ->whereIn('cpf', $this->insertedStudentCpfs)
                ->forceDelete();
        }

        if (!empty($this->insertedResponsibleCpfs)) {
            Responsible::withTrashed()
                ->whereIn('cpf', $this->insertedResponsibleCpfs)
                ->forceDelete();
        }

        parent::tearDown();
    }

    private function uniqueCpf(): string
    {
        return str_pad((string) random_int(10000000000, 99999999999), 11, '0', STR_PAD_LEFT);
    }

    public function test_can_insert_responsible_directly_into_neon(): void
    {
        $cpf = $this->uniqueCpf();
        $this->insertedResponsibleCpfs[] = $cpf;

        $responsible = Responsible::create([
            'name'         => '[TESTE] Responsável Direto',
            'phone_number' => '11999999999',
            'cpf'          => $cpf,
            'email'        => "teste_{$cpf}@teste.com",
            'birth_date'   => '1990-01-15',
            'address'      => 'Rua de Teste, 1, São Paulo',
        ]);

        $this->assertNotNull($responsible->id);

        $fromDb = DB::connection('pgsql')
            ->table('responsibles')
            ->where('cpf', $cpf)
            ->first();

        $this->assertNotNull($fromDb, 'Responsável não encontrado no Neon após insert.');
        $this->assertEquals('[TESTE] Responsável Direto', $fromDb->name);
    }

    public function test_can_insert_student_linked_to_responsible_into_neon(): void
    {
        $responsibleCpf = $this->uniqueCpf();
        $studentCpf     = $this->uniqueCpf();

        $this->insertedResponsibleCpfs[] = $responsibleCpf;
        $this->insertedStudentCpfs[]     = $studentCpf;

        $responsible = Responsible::create([
            'name'         => '[TESTE] Responsável com Aluno',
            'phone_number' => '11988888888',
            'cpf'          => $responsibleCpf,
            'email'        => "responsavel_{$responsibleCpf}@teste.com",
            'birth_date'   => '1985-06-20',
            'address'      => 'Av. Teste, 100, Campinas',
        ]);

        $student = Student::create([
            'responsible_id' => $responsible->id,
            'name'           => '[TESTE] Aluno',
            'cpf'            => $studentCpf,
            'rg'             => null,
            'birth_date'     => '2015-03-10',
            'modalidade'     => 'Jiu Jitsu',
        ]);

        $this->assertNotNull($student->id);

        $fromDb = DB::connection('pgsql')
            ->table('students')
            ->where('cpf', $studentCpf)
            ->first();

        $this->assertNotNull($fromDb, 'Aluno não encontrado no Neon após insert.');
        $this->assertEquals($responsible->id, $fromDb->responsible_id);
    }

    public function test_duplicate_cpf_raises_unique_constraint_in_neon(): void
    {
        $responsibleCpf = $this->uniqueCpf();
        $this->insertedResponsibleCpfs[] = $responsibleCpf;

        Responsible::create([
            'name'         => '[TESTE] Responsável Original',
            'phone_number' => '11966666666',
            'cpf'          => $responsibleCpf,
            'email'        => "orig_{$responsibleCpf}@teste.com",
            'birth_date'   => '1980-11-05',
            'address'      => 'Rua Original, 1',
        ]);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        Responsible::create([
            'name'         => '[TESTE] Responsável Duplicado',
            'phone_number' => '11955555555',
            'cpf'          => $responsibleCpf,
            'email'        => "dup_{$responsibleCpf}@teste.com",
            'birth_date'   => '1982-03-10',
            'address'      => 'Rua Duplicada, 2',
        ]);
    }
}
