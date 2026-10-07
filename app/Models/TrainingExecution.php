<?php

namespace App\Models;

use App\Enums\TrainingExecutionStatus;
use App\Traits\BelongsToTeacher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingExecution extends Model
{
    use BelongsToTeacher, HasFactory;

    protected $fillable = [
        'started_at',
        'completed_at',
        'duration',
        'distance',
        'average_heart_rate',
        'max_heart_rate',
        'average_pace',
        'average_power',
        'perceived_effort',
        'feeling',
        'notes',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'status' => TrainingExecutionStatus::class,
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
