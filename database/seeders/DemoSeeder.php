<?php

namespace Database\Seeders;

use App\Models\Responsible;
use App\Models\Student;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Massa de dados para desenvolvimento: um professor por modalidade e 150
     * alunos com a data de matrícula espalhada pelos últimos 15 meses, para
     * aparecer gente em todas as situações (em dia, vencendo e vencida).
     */
    public const PROFESSORES = [
        'prof_jiujitsu' => 'Professor de Jiu Jitsu',
        'prof_muaythai' => 'Professor de Muay Thai',
        'prof_taekwondo' => 'Professor de Taekwondo',
        'prof_boxe' => 'Professor de Boxe',
    ];

    public const ALUNOS = 150;

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('DemoSeeder ignorado: são dados fictícios e o ambiente é produção.');

            return;
        }

        if (User::withTrashed()->whereIn('username', array_keys(self::PROFESSORES))->exists()) {
            $this->command?->info('DemoSeeder já rodou; nada a fazer.');

            return;
        }

        foreach (self::PROFESSORES as $username => $name) {
            User::create([
                'name' => $name,
                'username' => $username,
                'role' => User::ROLE_PROFESSOR,
                'password' => Hash::make('heroisdotatame'),
                'email_verified_at' => now(),
            ]);
        }

        // Nomes e endereços em português, independente do faker_locale da app.
        $faker = Faker::create('pt_BR');

        // Um aluno a cada 3 dias, de hoje até ~15 meses atrás.
        for ($i = 0; $i < self::ALUNOS; $i++) {
            $sobrenome = $faker->lastName();

            Student::factory()
                ->for(Responsible::factory()->state([
                    'name' => $faker->firstName().' '.$sobrenome,
                    'address' => substr($faker->streetAddress(), 0, 150),
                    'neighborhood' => substr($faker->citySuffix(), 0, 80),
                ]))
                ->create([
                    'name' => $faker->firstName().' '.$sobrenome,
                    'father_name' => $faker->firstNameMale().' '.$sobrenome,
                    'mother_name' => $faker->firstNameFemale().' '.$sobrenome,
                    'school' => $faker->randomElement(['Colégio Estadual São José', 'Colégio Estadual Nossa Senhora do Rocio', 'Escola Municipal Duque de Caxias', 'Colégio Estadual de Prudentópolis']),
                    'matricula_em' => Carbon::today()->subDays($i * 3),
                    // Metade pendente, um quarto entregue, um quarto assinado.
                    'termo_status' => ['pendente', 'pendente', 'entregue', 'assinado'][$i % 4],
                ]);
        }

        $this->command?->info(count(self::PROFESSORES).' professores e '.self::ALUNOS.' alunos de demonstração criados.');
    }
}
