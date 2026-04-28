<?php

namespace Tests\Feature;

use App\Livewire\EnrollmentForm;
use App\Models\Responsible;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * @group neon
 *
 * Testa inserção real de dados no Neon (PostgreSQL de produção).
 * Cada teste limpa os próprios registros no tearDown para não poluir o banco.
 *
 * Pré-requisito: tabelas existentes — rode ./vendor/bin/sail artisan migrate antes.
 *
 * Para rodar: ./vendor/bin/sail artisan test --group=neon
 */
class NeonInsertionTest extends TestCase
{
    private array $insertedResponsibleCpfs = [];
    private array $insertedStudentCpfs = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (!getenv('RUN_NEON_TESTS')) {
            $this->markTestSkipped('Teste Neon inativo. Para rodar: RUN_NEON_TESTS=1 ./vendor/bin/sail artisan test --group=neon');
        }

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

    public function test_enrollment_form_submits_and_saves_to_neon(): void
    {
        $responsibleCpf = $this->uniqueCpf();
        $studentCpf     = $this->uniqueCpf();

        $this->insertedResponsibleCpfs[] = $responsibleCpf;
        $this->insertedStudentCpfs[]     = $studentCpf;

        Livewire::test(EnrollmentForm::class)
            ->set('responsible_name', '[TESTE] Via Formulário Livewire')
            ->set('responsible_phone_number', '11977777777')
            ->set('responsible_cpf', $responsibleCpf)
            ->set('responsible_email', "form_{$responsibleCpf}@teste.com")
            ->set('responsible_birth_date', '1988-04-22')
            ->set('responsible_address', 'Rua do Formulário, 42, São Paulo')
            ->set('student_name', '[TESTE] Aluno Via Form')
            ->set('student_cpf', $studentCpf)
            ->set('student_rg', '')
            ->set('student_birth_date', '2013-07-15')
            ->set('student_modalidade', 'Muay Thai')
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertHasNoErrors();

        $responsible = DB::connection('pgsql')
            ->table('responsibles')
            ->where('cpf', $responsibleCpf)
            ->first();

        $student = DB::connection('pgsql')
            ->table('students')
            ->where('cpf', $studentCpf)
            ->first();

        $this->assertNotNull($responsible, 'Responsável não salvo no Neon via formulário Livewire.');
        $this->assertNotNull($student, 'Aluno não salvo no Neon via formulário Livewire.');
        $this->assertEquals($responsible->id, $student->responsible_id);
    }

    public function test_duplicate_cpf_is_rejected_and_nothing_is_saved(): void
    {
        $responsibleCpf = $this->uniqueCpf();
        $this->insertedResponsibleCpfs[] = $responsibleCpf;

        Responsible::create([
            'name'         => '[TESTE] Responsável Existente',
            'phone_number' => '11966666666',
            'cpf'          => $responsibleCpf,
            'email'        => "dup_{$responsibleCpf}@teste.com",
            'birth_date'   => '1980-11-05',
            'address'      => 'Rua Duplicada, 1',
        ]);

        $studentCpf = $this->uniqueCpf();

        Livewire::test(EnrollmentForm::class)
            ->set('responsible_cpf', $responsibleCpf)
            ->set('student_cpf', $studentCpf)
            ->call('submit')
            ->assertHasErrors(['responsible_cpf']);

        $studentInDb = DB::connection('pgsql')
            ->table('students')
            ->where('cpf', $studentCpf)
            ->first();

        $this->assertNull($studentInDb, 'Aluno foi salvo mesmo com CPF de responsável duplicado.');
    }
}
