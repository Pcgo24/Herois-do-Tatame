<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    public string $cpf = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'cpf' => ['required', 'regex:/^\d{11}$/'],
            'password' => ['required', 'string'],
        ], [
            'cpf.required' => 'Informe seu CPF.',
            'cpf.regex' => 'O CPF deve conter 11 dígitos.',
            'password.required' => 'Informe sua senha.',
        ]);

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['cpf' => $this->cpf, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey(), 60);

            Log::warning('Tentativa de login malsucedida.', [
                'cpf' => $this->cpf,
                'ip' => request()->ip(),
            ]);

            throw ValidationException::withMessages([
                'cpf' => 'CPF ou senha inválidos.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        $this->redirect(route('admin.dashboard'), navigate: false);
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'cpf' => "Muitas tentativas. Tente novamente em {$seconds} segundos.",
        ]);
    }

    private function throttleKey(): string
    {
        return 'login:'.$this->cpf.'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.auth');
    }
}
