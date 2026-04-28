<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public static function enrollmentRules(): array
    {
        return [
            'responsible_name' => ['required', 'string', 'max:80'],
            'responsible_phone_number' => ['required', 'string', 'regex:/^\d{11}$/'],
            'responsible_cpf' => ['required', 'string', 'regex:/^\d{11}$/', 'unique:responsibles,cpf'],
            'responsible_email' => ['required', 'email', 'max:255'],
            'responsible_birth_date' => ['required', 'date', 'before:'.Carbon::now()->subYears(18)->format('Y-m-d')],
            'responsible_address' => ['required', 'string', 'max:150'],
            'student_name' => ['required', 'string', 'max:80'],
            'student_cpf' => ['required', 'string', 'regex:/^\d{11}$/', 'unique:students,cpf'],
            'student_rg' => ['nullable', 'string', 'max:20'],
            'student_birth_date' => [
                'required',
                'date',
                'before_or_equal:'.Carbon::now()->subYears(8)->format('Y-m-d'),
                'after:'.Carbon::now()->subYears(18)->format('Y-m-d'),
            ],
            'student_modalidade' => ['required', 'string', 'in:Jiu Jitsu,Muay Thai,Taekwondo,Boxe'],
        ];
    }

    public function rules(): array
    {
        return static::enrollmentRules();
    }

    public static function enrollmentMessages(): array
    {
        return [
            'responsible_name.required' => 'O nome do responsável é obrigatório.',
            'responsible_name.max' => 'O nome do responsável não pode ter mais de 80 caracteres.',
            'responsible_phone_number.required' => 'O telefone do responsável é obrigatório.',
            'responsible_phone_number.regex' => 'O telefone deve conter exatamente 11 dígitos (ex: 11999999999).',
            'responsible_cpf.required' => 'O CPF do responsável é obrigatório.',
            'responsible_cpf.regex' => 'O CPF deve conter exatamente 11 dígitos numéricos, sem pontos ou traços.',
            'responsible_cpf.unique' => 'Já existe um cadastro com este CPF de responsável.',
            'responsible_email.required' => 'O e-mail do responsável é obrigatório.',
            'responsible_email.email' => 'Informe um endereço de e-mail válido.',
            'responsible_birth_date.required' => 'A data de nascimento do responsável é obrigatória.',
            'responsible_birth_date.before' => 'O responsável deve ter pelo menos 18 anos.',
            'responsible_address.required' => 'O endereço é obrigatório.',
            'responsible_address.max' => 'O endereço não pode ter mais de 150 caracteres.',
            'student_name.required' => 'O nome do aluno é obrigatório.',
            'student_name.max' => 'O nome do aluno não pode ter mais de 80 caracteres.',
            'student_cpf.required' => 'O CPF do aluno é obrigatório.',
            'student_cpf.regex' => 'O CPF deve conter exatamente 11 dígitos numéricos, sem pontos ou traços.',
            'student_cpf.unique' => 'Já existe um aluno cadastrado com este CPF.',
            'student_birth_date.required' => 'A data de nascimento do aluno é obrigatória.',
            'student_birth_date.before_or_equal' => 'O aluno deve ter pelo menos 8 anos.',
            'student_birth_date.after' => 'O aluno deve ter no máximo 17 anos.',
            'student_modalidade.required' => 'Selecione uma modalidade.',
            'student_modalidade.in' => 'Modalidade inválida. Escolha entre: Jiu Jitsu, Muay Thai, Taekwondo ou Boxe.',
        ];
    }
}
