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
        'modalidade',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'responsible_id' => 'string',
            'birth_date' => 'date',
        ];
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(Responsible::class);
    }
}
