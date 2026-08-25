<?php

namespace Database\Seeders;

use App\Models\Responsible;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class StudentSeeder extends Seeder
{
    /**
     * Alunos de demonstração, com a ficha completa para o PDF sair sem lacunas.
     *
     * Um deles declara não possuir pai registrado, para exercitar o caminho da
     * filiação condicional na ficha e no dashboard.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('StudentSeeder ignorado: são dados fictícios e o ambiente é produção.');

            return;
        }

        $cadastros = [
            [
                'responsible' => [
                    'name' => 'Maria Aparecida Kovalchuk',
                    'phone_number' => '42998871234',
                    'home_phone' => '4232241187',
                    'cpf' => '52998224725',
                    'rg' => '104521873',
                    'email' => 'maria.kovalchuk@example.com',
                    'birth_date' => '1988-03-12',
                    'address' => 'Rua Sete de Setembro, 842',
                    'neighborhood' => 'Centro',
                ],
                'student' => [
                    'name' => 'Lucas Kovalchuk',
                    'cpf' => '39053344705',
                    'rg' => '135884201',
                    'birth_date' => Carbon::now()->subYears(12)->subMonths(4)->format('Y-m-d'),
                    'school' => 'Colégio Estadual São José',
                    'grade' => '7º ano',
                    'father_name' => 'Anderson Kovalchuk',
                    'mother_name' => 'Maria Aparecida Kovalchuk',
                    'phone' => null,
                    'email' => null,
                    'modalidade' => 'Boxe',
                    'termo_status' => 'assinado',
                ],
            ],
            [
                'responsible' => [
                    'name' => 'João Batista Ferreira',
                    'phone_number' => '42999123344',
                    'home_phone' => null,
                    'cpf' => '16899535009',
                    'rg' => '98443112',
                    'email' => 'joao.ferreira@example.com',
                    'birth_date' => '1979-11-02',
                    'address' => 'Avenida Brasil, 1520, casa 3',
                    'neighborhood' => 'Jardim Primavera',
                ],
                'student' => [
                    'name' => 'Ana Clara Ferreira',
                    'cpf' => '11144477735',
                    'rg' => '147992055',
                    'birth_date' => Carbon::now()->subYears(15)->subMonths(9)->format('Y-m-d'),
                    'school' => 'Colégio Estadual Nossa Senhora do Rocio',
                    'grade' => '1º ano do Ensino Médio',
                    'father_name' => 'João Batista Ferreira',
                    'mother_name' => 'Roseli Ferreira',
                    'phone' => '42998774411',
                    'email' => 'anaclara.ferreira@example.com',
                    'modalidade' => 'Muay Thai',
                    'termo_status' => 'entregue',
                ],
            ],
            [
                'responsible' => [
                    'name' => 'Solange Ribeiro dos Santos',
                    'phone_number' => '42996550012',
                    'home_phone' => '4232249900',
                    'cpf' => '19100000000',
                    'rg' => '112307744',
                    'email' => 'solange.santos@example.com',
                    'birth_date' => '1992-07-25',
                    'address' => 'Rua Marechal Floriano, 77',
                    'neighborhood' => 'Vila Nova',
                ],
                'student' => [
                    'name' => 'Pedro Henrique Ribeiro',
                    'cpf' => '22233344456',
                    'rg' => '151220388',
                    'birth_date' => Carbon::now()->subYears(9)->subMonths(2)->format('Y-m-d'),
                    'school' => 'Escola Municipal Duque de Caxias',
                    'grade' => '4º ano',
                    // Caminho da filiação condicional: a ficha imprime "Não declarado".
                    'father_name' => null,
                    'no_father' => true,
                    'mother_name' => 'Solange Ribeiro dos Santos',
                    'phone' => null,
                    'email' => null,
                    'modalidade' => 'Jiu Jitsu',
                    'termo_status' => 'pendente',
                ],
            ],
        ];

        foreach ($cadastros as $cadastro) {
            $responsible = Responsible::updateOrCreate(
                ['cpf' => $cadastro['responsible']['cpf']],
                $cadastro['responsible'],
            );

            Student::updateOrCreate(
                ['cpf' => $cadastro['student']['cpf']],
                $cadastro['student'] + ['responsible_id' => $responsible->id],
            );
        }

        $this->command?->info(count($cadastros).' alunos de demonstração criados.');
    }
}
