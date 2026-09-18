<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Student extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'responsible_id',
        'name',
        'cpf',
        'rg',
        'birth_date',
        'school',
        'grade',
        'father_name',
        'mother_name',
        'no_father',
        'no_mother',
        'phone',
        'email',
        'modalidade',
        'matricula_em',
        'termo_status',
        'termo_arquivo',
        'termo_arquivo_nome',
        'termo_arquivo_enviado_em',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'responsible_id' => 'string',
            'birth_date' => 'date',
            'matricula_em' => 'date',
            'no_father' => 'boolean',
            'no_mother' => 'boolean',
            'termo_arquivo_enviado_em' => 'datetime',
        ];
    }

    public const DIAS_AVISO_VENCIMENTO = 30;

    protected static function booted(): void
    {
        static::creating(function (Student $student) {
            $student->matricula_em ??= Carbon::today();
        });
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(Responsible::class);
    }

    // A matrícula vale um ano; renovar recomeça a contagem de hoje.
    public function getVenceEmAttribute(): Carbon
    {
        return $this->matricula_em->copy()->addYear();
    }

    public function diasParaVencer(): int
    {
        return (int) Carbon::today()->diffInDays($this->vence_em, false);
    }

    public function situacaoMatricula(): string
    {
        $dias = $this->diasParaVencer();

        return match (true) {
            $dias < 0 => 'vencida',
            $dias <= self::DIAS_AVISO_VENCIMENTO => 'vencendo',
            default => 'ok',
        };
    }

    public function textoVencimento(): string
    {
        $dias = $this->diasParaVencer();

        if ($dias === 0) {
            return 'vence hoje';
        }

        $prazo = self::prazoHumano(abs($dias), $this->vence_em);

        return $dias > 0
            ? (str_starts_with($prazo, '1 ') ? "falta {$prazo}" : "faltam {$prazo}")
            : "vencida há {$prazo}";
    }

    public function renovarMatricula(): void
    {
        $this->update(['matricula_em' => Carbon::today()]);
    }

    // Dentro da janela de aviso fala em dias, para o professor ver a urgência;
    // fora dela, em meses inteiros.
    private static function prazoHumano(int $dias, Carbon $venceEm): string
    {
        $meses = (int) abs(Carbon::today()->diffInMonths($venceEm));

        if ($dias > self::DIAS_AVISO_VENCIMENTO && $meses >= 1) {
            return $meses === 1 ? '1 mês' : "{$meses} meses";
        }

        return $dias === 1 ? '1 dia' : "{$dias} dias";
    }
}
