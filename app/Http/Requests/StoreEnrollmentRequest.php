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

    public static function enrollmentRules(array $state = []): array
    {
        $noFather = filter_var($state['student_no_father'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $noMother = filter_var($state['student_no_mother'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return [
            'responsible_name' => ['required', 'string', 'max:80', 'regex:/^[\pL\pN\s\'\-]+$/u'],
            'responsible_phone_number' => ['required', 'string', 'regex:/^\d{10,11}$/'],
            'responsible_home_phone' => ['nullable', 'string', 'regex:/^\d{10,11}$/'],
            'responsible_cpf' => ['required', 'string', 'regex:/^\d{11}$/', 'unique:responsibles,cpf'],
            'responsible_rg' => ['required', 'string', 'regex:/^\d{7,9}$/'],
            'responsible_email' => ['required', 'email', 'max:255'],
            'responsible_birth_date' => ['required', 'date', 'before:'.Carbon::now()->subYears(18)->format('Y-m-d')],
            'responsible_address' => ['required', 'string', 'max:150'],
            'responsible_neighborhood' => ['required', 'string', 'max:80'],
            'student_name' => ['required', 'string', 'max:80', 'regex:/^[\pL\pN\s\'\-]+$/u'],
            'student_cpf' => ['required', 'string', 'regex:/^\d{11}$/', 'unique:students,cpf'],
            'student_rg' => ['required', 'string', 'regex:/^\d{7,9}$/'],
            'student_birth_date' => [
                'required',
                'date',
                'before_or_equal:'.Carbon::now()->subYears(8)->format('Y-m-d'),
                'after:'.Carbon::now()->subYears(18)->format('Y-m-d'),
            ],
            'student_school' => ['required', 'string', 'max:120'],
            'student_grade' => ['required', 'string', 'max:30'],
            'student_father_name' => $noFather
                ? ['nullable']
                : ['required', 'string', 'max:80', 'regex:/^[\pL\pN\s\'\-]+$/u'],
            'student_mother_name' => $noMother
                ? ['nullable']
                : ['required', 'string', 'max:80', 'regex:/^[\pL\pN\s\'\-]+$/u'],
            'student_no_father' => ['boolean'],
            'student_no_mother' => ['boolean'],
            'student_phone' => ['nullable', 'string', 'regex:/^\d{10,11}$/'],
            'student_email' => ['nullable', 'email', 'max:255'],
            'student_modalidade' => ['required', 'string', 'in:Jiu Jitsu,Muay Thai,Taekwondo,Boxe'],
            'lgpd_consent' => ['accepted'],
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
            'responsible_name.regex' => 'O nome do responsável só pode conter letras, números, espaços, hífens e apóstrofos.',
            'responsible_phone_number.required' => 'O telefone do responsável é obrigatório.',
            'responsible_phone_number.regex' => 'O telefone deve conter 10 ou 11 dígitos (ex: (42) 9999-9999 ou (42) 9 9999-9999).',
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
            'student_name.regex' => 'O nome do aluno só pode conter letras, números, espaços, hífens e apóstrofos.',
            'student_cpf.required' => 'O CPF do aluno é obrigatório.',
            'student_cpf.regex' => 'O CPF deve conter exatamente 11 dígitos numéricos, sem pontos ou traços.',
            'student_cpf.unique' => 'Já existe um aluno cadastrado com este CPF.',
            'student_birth_date.required' => 'A data de nascimento do aluno é obrigatória.',
            'student_birth_date.before_or_equal' => 'O aluno deve ter pelo menos 8 anos.',
            'student_birth_date.after' => 'O aluno deve ter no máximo 17 anos.',
            'student_modalidade.required' => 'Selecione uma modalidade.',
            'student_modalidade.in' => 'Modalidade inválida. Escolha entre: Jiu Jitsu, Muay Thai, Taekwondo ou Boxe.',
            'responsible_home_phone.regex' => 'O telefone residencial deve conter 10 ou 11 dígitos.',
            'responsible_rg.required' => 'O RG do responsável é obrigatório — ele consta na autorização da ficha.',
            'responsible_rg.regex' => 'O RG deve conter de 7 a 9 dígitos numéricos, sem pontos ou traços.',
            'responsible_neighborhood.required' => 'O bairro é obrigatório.',
            'responsible_neighborhood.max' => 'O bairro não pode ter mais de 80 caracteres.',
            'student_rg.required' => 'O RG do aluno é obrigatório — ele consta na autorização da ficha.',
            'student_rg.regex' => 'O RG deve conter de 7 a 9 dígitos numéricos, sem pontos ou traços.',
            'student_school.required' => 'A escola do aluno é obrigatória.',
            'student_school.max' => 'O nome da escola não pode ter mais de 120 caracteres.',
            'student_grade.required' => 'A série do aluno é obrigatória.',
            'student_grade.max' => 'A série não pode ter mais de 30 caracteres.',
            'student_father_name.required' => 'Informe a filiação do pai ou marque que não possui.',
            'student_father_name.regex' => 'O nome do pai só pode conter letras, espaços, hífens e apóstrofos.',
            'student_mother_name.required' => 'Informe a filiação da mãe ou marque que não possui.',
            'student_mother_name.regex' => 'O nome da mãe só pode conter letras, espaços, hífens e apóstrofos.',
            'student_phone.regex' => 'O celular do aluno deve conter 10 ou 11 dígitos.',
            'student_email.email' => 'Informe um e-mail válido para o aluno ou deixe o campo vazio.',
            'lgpd_consent.accepted' => 'Você precisa concordar com o uso dos dados para prosseguir.',
        ];
    }
}
