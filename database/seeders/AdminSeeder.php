<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        if (User::withTrashed()->where('role', User::ROLE_ADMIN)->exists()) {
            $this->command?->info('Já existe administrador; nada a fazer.');

            return;
        }

        $username = Str::lower(trim((string) config('admin.username')));

        User::create([
            'name' => config('admin.name'),
            'username' => $username,
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make(config('admin.password')),
            'email_verified_at' => now(),
        ]);

        $this->command?->info("Administrador criado: {$username}.");
    }
}
