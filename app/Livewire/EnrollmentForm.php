<?php

namespace App\Livewire;

use App\Http\Requests\StoreEnrollmentRequest;
use App\Models\Responsible;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class EnrollmentForm extends Component
{
    public string $responsible_name = '';

    public string $responsible_phone_number = '';

    public string $responsible_cpf = '';

    public string $responsible_email = '';

    public string $responsible_birth_date = '';

    public string $responsible_address = '';

    public string $responsible_rg = '';

    public string $responsible_neighborhood = '';

    public ?string $responsible_home_phone = null;

    public string $student_name = '';

    public string $student_cpf = '';

    public string $student_rg = '';

    public string $student_birth_date = '';

    public string $student_school = '';

    public string $student_grade = '';

    public ?string $student_father_name = null;

    public bool $student_no_father = false;

    public ?string $student_mother_name = null;

    public bool $student_no_mother = false;

    public ?string $student_phone = null;

    public ?string $student_email = null;

    public string $student_modalidade = '';

    public bool $submitted = false;

    public bool $lgpd_consent = false;

    public function submit(): void
    {
        Log::info('Tentativa de matrícula iniciada.', ['ip' => request()->ip()]);

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

        try {
            DB::transaction(function () use ($validated) {
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

                Log::info('Matrícula realizada com sucesso.', [
                    'responsible_id' => $responsible->id,
                    'student_id' => $student->id,
                ]);
            });

            $this->submitted = true;
        } catch (\Throwable $e) {
            Log::error('Falha ao salvar matrícula.', [
                'ip' => request()->ip(),
                'exception' => $e->getMessage(),
            ]);

            $this->addError('general', 'Ocorreu um erro ao processar a matrícula. Por favor, tente novamente.');
        }
    }

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

    public function render()
    {
        return view('livewire.enrollment-form')
            ->layout('layouts.app');
    }
}
