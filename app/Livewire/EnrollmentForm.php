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

    public string $student_name = '';

    public string $student_cpf = '';

    public string $student_rg = '';

    public string $student_birth_date = '';

    public string $student_modalidade = '';

    public bool $submitted = false;

    public function submit(): void
    {
        Log::info('Tentativa de matrícula iniciada.', ['ip' => request()->ip()]);

        $validated = $this->validate(
            StoreEnrollmentRequest::enrollmentRules(),
            StoreEnrollmentRequest::enrollmentMessages()
        );

        try {
            DB::transaction(function () use ($validated) {
                $responsible = Responsible::create([
                    'name' => $validated['responsible_name'],
                    'phone_number' => $validated['responsible_phone_number'],
                    'cpf' => $validated['responsible_cpf'],
                    'email' => $validated['responsible_email'],
                    'birth_date' => $validated['responsible_birth_date'],
                    'address' => $validated['responsible_address'],
                ]);

                $student = Student::create([
                    'responsible_id' => $responsible->id,
                    'name' => $validated['student_name'],
                    'cpf' => $validated['student_cpf'],
                    'rg' => $validated['student_rg'] ?: null,
                    'birth_date' => $validated['student_birth_date'],
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

    public function render()
    {
        return view('livewire.enrollment-form')
            ->layout('layouts.app');
    }
}
