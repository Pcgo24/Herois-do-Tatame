<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    public string $username = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->username = Str::lower(trim($this->username));

        $this->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Informe seu usuário.',
            'password.required' => 'Informe sua senha.',
        ]);

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['username' => $this->username, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey(), 60);

            Log::warning('Tentativa de login malsucedida.', [
                'username' => $this->username,
                'ip' => request()->ip(),
            ]);

            throw ValidationException::withMessages([
                'username' => 'Usuário ou senha inválidos.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        session()->regenerate();

        $this->redirect(route(Auth::user()->homeRoute()), navigate: false);
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => "Muitas tentativas. Tente novamente em {$seconds} segundos.",
        ]);
    }

    private function throttleKey(): string
    {
        return 'login:'.$this->username.'|'.request()->ip();
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.auth');
    }
}
