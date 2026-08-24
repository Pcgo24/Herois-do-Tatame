<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
            'no_father' => 'boolean',
            'no_mother' => 'boolean',
            'termo_arquivo_enviado_em' => 'datetime',
        ];
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(Responsible::class);
    }
}
