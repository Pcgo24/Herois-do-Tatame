<?php

namespace App\Livewire\Admin;

use App\Models\Student;
use Illuminate\Support\Collection;
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

    public bool $showCancelled = false;

    public bool $onlyAttention = false;

    // '' mostra todos; senão, um dos valores de students.termo_status.
    public string $termoFilter = '';

    // Pop-up de vencimentos: abre uma vez por sessão, na primeira visita ao
    // dashboard em que há matrícula vencida ou a vencer em 30 dias.
    public const SESSION_AVISO = 'aviso_vencimentos_visto';

    public bool $avisoAberto = false;

    public Collection $avisoVencidas;

    public Collection $avisoVencendo;

    public function mount(): void
    {
        $this->avisoVencidas = collect();
        $this->avisoVencendo = collect();

        if (session()->has(self::SESSION_AVISO)) {
            return;
        }

        $atencao = Student::query()->get()->filter(fn (Student $s) => $s->situacaoMatricula() !== 'ok');

        // Vencidas: as mais recentes primeiro (provavelmente ainda frequentam).
        // A vencer: as mais próximas do vencimento primeiro.
        $this->avisoVencidas = $atencao->filter(fn (Student $s) => $s->situacaoMatricula() === 'vencida')
            ->sortByDesc(fn (Student $s) => $s->diasParaVencer())->values();
        $this->avisoVencendo = $atencao->filter(fn (Student $s) => $s->situacaoMatricula() === 'vencendo')
            ->sortBy(fn (Student $s) => $s->diasParaVencer())->values();
        $this->avisoAberto = $atencao->isNotEmpty();
    }

    public function dismissAviso(): void
    {
        session()->put(self::SESSION_AVISO, true);
        $this->avisoAberto = false;
    }

    public function renovarMatricula(string $studentId): void
    {
        Student::findOrFail($studentId)->renovarMatricula();
    }

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

    // Cancelar é soft delete: o responsável e a ficha assinada ficam no lugar,
    // porque reativar precisa devolver a matrícula exatamente como estava.
    public function cancelEnrollment(string $studentId): void
    {
        Student::findOrFail($studentId)->delete();
    }

    public function restoreEnrollment(string $studentId): void
    {
        Student::onlyTrashed()->findOrFail($studentId)->restore();
    }

    private function fichasDisk(): string
    {
        return config('fichas.disk');
    }

    public function render()
    {
        return view('livewire.admin.dashboard', [
            'students' => Student::query()
                ->when($this->showCancelled, fn ($q) => $q->withTrashed())
                ->when($this->termoFilter !== '', fn ($q) => $q->where('termo_status', $this->termoFilter))
                ->with('responsible')
                ->orderBy('created_at')
                ->get()
                ->when($this->onlyAttention, fn ($alunos) => $alunos->filter(
                    fn (Student $s) => ! $s->trashed() && $s->situacaoMatricula() !== 'ok'
                ))
                ->sortBy(fn (Student $s) => $s->trashed() ? PHP_INT_MAX : $s->diasParaVencer())
                ->values(),
        ])->layout('layouts.admin');
    }
}
