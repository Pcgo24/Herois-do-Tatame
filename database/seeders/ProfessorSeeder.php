<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProfessorSeeder extends Seeder
{
    public function run(): void
    {
        // Este seeder roda a cada deploy, mas é apenas o bootstrap do primeiro
        // acesso. Havendo qualquer usuário — inclusive um removido pelo painel —
        // ele não mexe em nada: senha, nome e a lista de usuários passam a ser
        // geridos na tela de usuários, não pelas variáveis de ambiente.
        if (User::withTrashed()->exists()) {
            $this->command?->info('Já existe usuário cadastrado; nada a fazer.');

            return;
        }

        $username = (string) config('professor.username');

        User::create([
            'name' => config('professor.name'),
            'username' => $username,
            'password' => Hash::make(config('professor.password')),
            'email_verified_at' => now(),
        ]);

        $this->command?->info("Primeiro usuário criado: {$username}.");
    }
}
