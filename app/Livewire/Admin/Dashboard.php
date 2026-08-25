<?php

namespace App\Livewire\Admin;

use App\Models\Student;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Dashboard extends Component
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $signedFicha = null;

    public string $uploadTargetId = '';

    public function updateTermoStatus(string $studentId, string $status): void
    {
        if (! in_array($status, ['pendente', 'entregue', 'assinado'])) {
            return;
        }

        Student::findOrFail($studentId)->update(['termo_status' => $status]);
    }

    public function uploadSignedFicha(): void
    {
        $this->validate([
            'uploadTargetId' => ['required', 'uuid'],
            'signedFicha' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'signedFicha.required' => 'Escolha o arquivo da ficha assinada.',
            'signedFicha.mimes' => 'A ficha assinada deve ser um PDF ou uma imagem (JPG ou PNG).',
            'signedFicha.max' => 'O arquivo não pode passar de 5 MB.',
        ]);

        $student = Student::findOrFail($this->uploadTargetId);

        if ($student->termo_arquivo) {
            Storage::disk($this->fichasDisk())->delete($student->termo_arquivo);
        }

        $path = $this->signedFicha->storeAs(
            'fichas-assinadas',
            $student->id.'-'.Str::random(8).'.'.$this->signedFicha->getClientOriginalExtension(),
            $this->fichasDisk(),
        );

        $student->update([
            'termo_arquivo' => $path,
            'termo_arquivo_nome' => $this->signedFicha->getClientOriginalName(),
            'termo_arquivo_enviado_em' => now(),
            'termo_status' => 'assinado',
        ]);

        $this->reset('signedFicha', 'uploadTargetId');
    }

    public function removeSignedFicha(string $studentId): void
    {
        $student = Student::findOrFail($studentId);

        if ($student->termo_arquivo) {
            Storage::disk($this->fichasDisk())->delete($student->termo_arquivo);
        }

        $student->update([
            'termo_arquivo' => null,
            'termo_arquivo_nome' => null,
            'termo_arquivo_enviado_em' => null,
            'termo_status' => 'entregue',
        ]);
    }

    private function fichasDisk(): string
    {
        return config('fichas.disk');
    }

    public function render()
    {
        return view('livewire.admin.dashboard', [
            'students' => Student::with('responsible')->orderBy('created_at')->get(),
        ])->layout('layouts.admin');
    }
}
