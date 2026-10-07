<?php

namespace App\Models;

use App\Enums\TrainingSessionStatus;
use App\Traits\BelongsToTeacher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TrainingSession extends Model
{
    use BelongsToTeacher, HasFactory;

    protected $fillable = [
        'sport_id',
        'exercise_id',
        'title',
        'description',
        'scheduled_date',
        'estimated_duration',
        'distance',
        'intensity',
        'target_pace',
        'target_heart_rate',
        'target_power',
        'instructions',
        'coach_notes',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'status' => TrainingSessionStatus::class,
    ];

    public function week(): BelongsTo
    {
        return $this->belongsTo(TrainingWeek::class, 'training_week_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TrainingSessionItem::class)->orderBy('sort_order');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(TrainingExecution::class);
    }

    /**
     * Execução do próprio treino (sessão já pertence a um único aluno).
     */
    public function execution(): HasOne
    {
        return $this->hasOne(TrainingExecution::class);
    }
}
