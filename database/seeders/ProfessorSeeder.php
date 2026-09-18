<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProfessorSeeder extends Seeder
{
    public function run(): void
    {
        // Este seeder roda a cada deploy, mas é apenas o bootstrap do primeiro
        // acesso. Havendo qualquer professor — inclusive um removido pelo painel —
        // ele não mexe em nada: senha, nome e a lista de usuários passam a ser
        // geridos na tela de usuários, não pelas variáveis de ambiente. Admins
        // não contam: são outro bootstrap (AdminSeeder) e não dão aula.
        if (User::withTrashed()->where('role', User::ROLE_PROFESSOR)->exists()) {
            $this->command?->info('Já existe professor cadastrado; nada a fazer.');

            return;
        }

        $username = Str::lower(trim((string) config('professor.username')));

        User::create([
            'name' => config('professor.name'),
            'username' => $username,
            'role' => User::ROLE_PROFESSOR,
            'password' => Hash::make(config('professor.password')),
            'email_verified_at' => now(),
        ]);

        $this->command?->info("Primeiro usuário criado: {$username}.");
    }
}
