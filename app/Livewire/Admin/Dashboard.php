<?php

namespace App\Livewire\Admin;

use App\Models\Student;
use Livewire\Component;

class Dashboard extends Component
{
    public function updateTermoStatus(string $studentId, string $status): void
    {
        if (!in_array($status, ['pendente', 'entregue', 'assinado'])) {
            return;
        }

        Student::findOrFail($studentId)->update(['termo_status' => $status]);
    }

    public function render()
    {
        return view('livewire.admin.dashboard', [
            'students' => Student::with('responsible')->orderBy('created_at')->get(),
        ])->layout('layouts.admin');
    }
}
