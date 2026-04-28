<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Responsible extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'phone_number',
        'cpf',
        'email',
        'birth_date',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'birth_date' => 'date',
        ];
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
