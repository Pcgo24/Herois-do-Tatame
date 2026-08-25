<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProfessorSeeder extends Seeder
{
    public function run(): void
    {
        $cpf = preg_replace('/\D/', '', (string) config('professor.cpf'));

        User::updateOrCreate(
            ['cpf' => $cpf],
            [
                'name' => config('professor.name'),
                'email' => config('professor.email'),
                'password' => Hash::make(config('professor.password')),
                'email_verified_at' => now(),
            ],
        );

        $this->command?->info("Professor disponível para login com o CPF {$cpf}.");
    }
}
