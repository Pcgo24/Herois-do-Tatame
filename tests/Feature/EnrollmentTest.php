<?php

namespace Tests\Feature;

use App\Livewire\EnrollmentForm;
use App\Models\Responsible;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_enrollment_form_renders(): void
    {
        Livewire::test(EnrollmentForm::class)->assertStatus(200);
    }

    public function test_valid_submission_creates_responsible_and_student(): void
    {
        $this->fillForm()->call('submit')
            ->assertSet('submitted', true)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('responsibles', ['cpf' => '12345678901']);
        $this->assertDatabaseHas('students', ['cpf' => '98765432100']);
    }

    public function test_student_is_linked_to_responsible(): void
    {
        $this->fillForm()->call('submit');

        $responsible = Responsible::where('cpf', '12345678901')->first();
        $student = Student::where('cpf', '98765432100')->first();

        $this->assertNotNull($responsible);
        $this->assertNotNull($student);
        $this->assertEquals($responsible->id, $student->responsible_id);
    }

    public function test_all_required_fields_are_validated(): void
    {
        Livewire::test(EnrollmentForm::class)
            ->call('submit')
            ->assertHasErrors([
                'responsible_name',
                'responsible_phone_number',
                'responsible_cpf',
                'responsible_email',
                'responsible_birth_date',
                'responsible_address',
                'student_name',
                'student_cpf',
                'student_birth_date',
                'student_modalidade',
                'lgpd_consent',
            ]);
    }

    public function test_responsible_name_max_length_is_enforced(): void
    {
        Livewire::test(EnrollmentForm::class)
            ->set('responsible_name', str_repeat('a', 81))
            ->call('submit')
            ->assertHasErrors(['responsible_name' => 'max']);
    }

    public function test_student_name_max_length_is_enforced(): void
    {
        Livewire::test(EnrollmentForm::class)
            ->set('student_name', str_repeat('a', 81))
            ->call('submit')
            ->assertHasErrors(['student_name' => 'max']);
    }

    public function test_address_max_length_is_enforced(): void
    {
        Livewire::test(EnrollmentForm::class)
            ->set('responsible_address', str_repeat('a', 151))
            ->call('submit')
            ->assertHasErrors(['responsible_address' => 'max']);
    }

    public function test_responsible_cpf_must_be_11_digits(): void
    {
        Livewire::test(EnrollmentForm::class)
            ->set('responsible_cpf', '123.456.789-01')
            ->call('submit')
            ->assertHasErrors(['responsible_cpf']);
    }

    public function test_responsible_cpf_rejects_letters(): void
    {
        Livewire::test(EnrollmentForm::class)
            ->set('responsible_cpf', 'abcdefghijk')
            ->call('submit')
            ->assertHasErrors(['responsible_cpf']);
    }

    public function test_phone_must_be_11_digits(): void
    {
        Livewire::test(EnrollmentForm::class)
            ->set('responsible_phone_number', '119999999')
            ->call('submit')
            ->assertHasErrors(['responsible_phone_number']);
    }

    public function test_responsible_must_be_at_least_18_years_old(): void
    {
        Livewire::test(EnrollmentForm::class)
            ->set('responsible_birth_date', now()->subYears(17)->format('Y-m-d'))
            ->call('submit')
            ->assertHasErrors(['responsible_birth_date']);
    }

    public function test_student_must_be_at_least_8_years_old(): void
    {
        Livewire::test(EnrollmentForm::class)
            ->set('student_birth_date', now()->subYears(7)->format('Y-m-d'))
            ->call('submit')
            ->assertHasErrors(['student_birth_date']);
    }

    public function test_student_must_be_at_most_17_years_old(): void
    {
        Livewire::test(EnrollmentForm::class)
            ->set('student_birth_date', now()->subYears(18)->subDay()->format('Y-m-d'))
            ->call('submit')
            ->assertHasErrors(['student_birth_date']);
    }

    public function test_student_modalidade_must_be_valid(): void
    {
        Livewire::test(EnrollmentForm::class)
            ->set('student_modalidade', 'Karate')
            ->call('submit')
            ->assertHasErrors(['student_modalidade']);
    }

    public function test_responsible_cpf_must_be_unique(): void
    {
        Responsible::factory()->create(['cpf' => '12345678901']);

        $this->fillForm()->call('submit')
            ->assertHasErrors(['responsible_cpf']);
    }

    public function test_student_cpf_must_be_unique(): void
    {
        Student::factory()->create(['cpf' => '98765432100']);

        $this->fillForm()->call('submit')
            ->assertHasErrors(['student_cpf']);
    }

    // O RG deixou de ser opcional: a autorização da ficha da SMER identifica o
    // menor pelo "portador da Cédula de Identidade RG nº", então sem ele o
    // documento não pode ser emitido.
    public function test_student_rg_is_required(): void
    {
        $this->fillForm(['student_rg' => ''])->call('submit')
            ->assertSet('submitted', false)
            ->assertHasErrors(['student_rg' => 'required']);

        $this->assertDatabaseCount('students', 0);
    }

    public function test_logs_enrollment_attempt(): void
    {
        Log::spy();

        Livewire::test(EnrollmentForm::class)->call('submit');

        Log::shouldHaveReceived('info')
            ->with('Tentativa de matrícula iniciada.', \Mockery::any())
            ->once();
    }

    public function test_logs_successful_enrollment(): void
    {
        Log::spy();

        $this->fillForm()->call('submit');

        Log::shouldHaveReceived('info')
            ->with('Matrícula realizada com sucesso.', \Mockery::any())
            ->once();
    }

    public function test_xss_payload_is_rejected_by_name_validation(): void
    {
        $xss = '<script>alert("xss")</script>';

        $this->fillForm(['responsible_name' => $xss])->call('submit')
            ->assertHasErrors(['responsible_name']);
    }

    public function test_lgpd_consent_is_required(): void
    {
        $this->fillForm(['lgpd_consent' => false])
            ->call('submit')
            ->assertHasErrors(['lgpd_consent']);
    }

    public function test_submission_blocked_without_lgpd_consent(): void
    {
        $this->fillForm(['lgpd_consent' => false])->call('submit');

        $this->assertDatabaseMissing('responsibles', ['cpf' => '12345678901']);
        $this->assertDatabaseMissing('students', ['cpf' => '98765432100']);
    }

    public function test_enrollment_route_is_accessible_at_english_url(): void
    {
        $this->get('/enrollment')->assertStatus(200);
    }

    public function test_old_matricula_url_no_longer_exists(): void
    {
        $this->get('/matricula')->assertStatus(404);
    }

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

    // AJ-02: o handler Alpine apagava o campo ao ler um ano parcial (ex.: "0002"
    // enquanto se digita "2015"). Recusar data inválida é trabalho da validação
    // do servidor, não de um handler que descarta o que o usuário digitou.
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
}
