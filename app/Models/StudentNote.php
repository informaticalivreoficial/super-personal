<?php

namespace App\Models;

use App\Enums\NoteVisibility;
use App\Traits\BelongsToTeacher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Observações privadas do treinador sobre o aluno.
 * visibility = private nunca aparece para o aluno.
 */
class StudentNote extends Model
{
    use BelongsToTeacher, HasFactory;

    protected $fillable = [
        'content',
        'visibility',
    ];

    protected $casts = [
        'visibility' => NoteVisibility::class,
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
