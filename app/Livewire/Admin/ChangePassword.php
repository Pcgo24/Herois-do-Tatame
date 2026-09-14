<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class ChangePassword extends Component
{
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $saved = false;

    public function save(): void
    {
        $this->saved = false;

        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Informe sua senha atual.',
            'current_password.current_password' => 'A senha atual não confere.',
            'password.required' => 'Informe a nova senha.',
            'password.min' => 'A nova senha precisa ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação não confere com a nova senha.',
        ]);

        Auth::user()->update(['password' => Hash::make($this->password)]);

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->saved = true;
    }

    public function render()
    {
        return view('livewire.admin.change-password')->layout('layouts.admin');
    }
}
