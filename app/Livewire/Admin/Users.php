<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Users extends Component
{
    public bool $showRemoved = false;

    public bool $modalOpen = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $username = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function create(): void
    {
        $this->resetForm();
        $this->modalOpen = true;
    }

    public function edit(int $id): void
    {
        $user = User::professors()->findOrFail($id);

        $this->resetForm();
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->modalOpen = true;
    }

    public function save(): void
    {
        $this->username = Str::lower(trim($this->username));

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'username' => [
                'required', 'string', 'regex:/^[a-z0-9_.]{3,30}$/',
                // withTrashed: um removido ainda ocupa o índice único do banco,
                // e restaurá-lo depois não pode colidir com um cadastro novo.
                Rule::unique('users', 'username')->ignore($this->editingId),
            ],
            // Ao editar, senha em branco significa "manter a atual".
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];

        $this->validate($rules, [
            'name.required' => 'Informe o nome.',
            'username.required' => 'Informe o usuário.',
            'username.regex' => 'Use de 3 a 30 caracteres: letras minúsculas, números, ponto ou sublinhado.',
            'username.unique' => 'Este usuário já está em uso.',
            'password.required' => 'Informe a senha.',
            'password.min' => 'A senha precisa ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação não confere com a senha.',
        ]);

        $data = ['name' => $this->name, 'username' => $this->username, 'role' => User::ROLE_PROFESSOR];

        if ($this->password !== '') {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingId) {
            User::professors()->findOrFail($this->editingId)->update($data);
        } else {
            User::create($data + ['email_verified_at' => now()]);
        }

        $this->resetForm();
        $this->modalOpen = false;
    }

    public function remove(int $id): void
    {
        $user = User::professors()->findOrFail($id);

        if ($user->id === Auth::id()) {
            throw ValidationException::withMessages(['remove' => 'Você não pode remover o próprio usuário.']);
        }

        // Admins não dão aula: o sistema precisa de um professor ativo.
        if (User::professors()->count() <= 1) {
            throw ValidationException::withMessages(['remove' => 'O sistema precisa manter ao menos um professor ativo.']);
        }

        $user->delete();
    }

    public function restore(int $id): void
    {
        User::professors()->onlyTrashed()->findOrFail($id)->restore();
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->modalOpen = false;
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'name', 'username', 'password', 'password_confirmation');
        $this->resetValidation();
    }

    public function render()
    {
        // Admins ficam fora da lista em qualquer situação: o professor não
        // sabe que existem, e nem o próprio admin se administra por aqui.
        $query = User::professors();

        if ($this->showRemoved) {
            $query->withTrashed();
        }

        return view('livewire.admin.users', [
            'users' => $query->orderBy('name')->get(),
        ])->layout('layouts.admin');
    }
}
