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

        // O sistema tem exatamente um professor: não há tela de registro nem
        // papéis. Sem esta limpeza, trocar PROFESSOR_CPF criaria um segundo
        // usuário e deixaria o antigo capaz de logar com a senha antiga.
        //
        // Ela vem antes do upsert de propósito: users.email é único, e o
        // usuário obsoleto ainda seguraria o e-mail que estamos prestes a usar.
        $orfaos = User::where(fn ($q) => $q->where('cpf', '!=', $cpf)->orWhereNull('cpf'))->delete();

        if ($orfaos > 0) {
            $this->command?->warn("{$orfaos} usuário(s) com outro CPF removido(s).");
        }

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
