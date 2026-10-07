<?php

namespace App\Models;

use App\Enums\TrainingPlanStatus;
use App\Traits\BelongsToTeacher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingPlan extends Model
{
    use BelongsToTeacher, HasFactory;

    protected $fillable = [
        'name',
        'description',
        'goal',
        'start_date',
        'end_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => TrainingPlanStatus::class,
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function weeks(): HasMany
    {
        return $this->hasMany(TrainingWeek::class)->orderBy('week_number');
    }

    public function sessions(): HasMany
    {
        return $this->hasManyThrough(
            TrainingSession::class,
            TrainingWeek::class,
            'training_plan_id',
            'training_week_id'
        );
    }
}
