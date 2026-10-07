<?php

namespace App\Models;

use App\Traits\BelongsToTeacher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Avaliações físicas/evolução do aluno — histórico somente-leitura.
 * Cada registro é uma nova linha; nada é sobrescrito.
 */
class StudentProgress extends Model
{
    use BelongsToTeacher, HasFactory;

    protected $fillable = [
        'recorded_at',
        'weight',
        'body_fat',
        'measurements',
        'resting_heart_rate',
        'max_heart_rate',
        'ftp',
        'running_pace',
        'swimming_pace',
        'notes',
    ];

    protected $casts = [
        'recorded_at' => 'date',
        'measurements' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
