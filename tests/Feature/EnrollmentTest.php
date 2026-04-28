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
            'responsible_cpf' => '12345678901',
            'responsible_email' => 'maria@email.com',
            'responsible_birth_date' => '1990-01-15',
            'responsible_address' => 'Rua das Flores, 123, São Paulo',
            'student_name' => 'João da Silva',
            'student_cpf' => '98765432100',
            'student_rg' => '123456789',
            'student_birth_date' => '2015-06-10',
            'student_modalidade' => 'Jiu Jitsu',
        ];
    }

    private function fillForm(array $overrides = []): mixed
    {
        $data = array_merge($this->validPayload(), $overrides);

        return Livewire::test(EnrollmentForm::class)
            ->set('responsible_name', $data['responsible_name'])
            ->set('responsible_phone_number', $data['responsible_phone_number'])
            ->set('responsible_cpf', $data['responsible_cpf'])
            ->set('responsible_email', $data['responsible_email'])
            ->set('responsible_birth_date', $data['responsible_birth_date'])
            ->set('responsible_address', $data['responsible_address'])
            ->set('student_name', $data['student_name'])
            ->set('student_cpf', $data['student_cpf'])
            ->set('student_rg', $data['student_rg'])
            ->set('student_birth_date', $data['student_birth_date'])
            ->set('student_modalidade', $data['student_modalidade']);
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

    public function test_student_rg_is_optional(): void
    {
        $this->fillForm(['student_rg' => ''])->call('submit')
            ->assertSet('submitted', true)
            ->assertHasNoErrors();

        $student = Student::where('cpf', '98765432100')->first();
        $this->assertNull($student->rg);
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

    public function test_xss_payload_is_stored_as_plain_text(): void
    {
        $xss = '<script>alert("xss")</script>';

        $this->fillForm(['responsible_name' => $xss])->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('responsibles', ['name' => $xss]);
    }
}
