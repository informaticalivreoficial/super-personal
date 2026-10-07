<?php

namespace App\Models;

use App\Enums\TrainingWeekStatus;
use App\Traits\BelongsToTeacher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingWeek extends Model
{
    use BelongsToTeacher, HasFactory;

    protected $fillable = [
        'week_number',
        'name',
        'start_date',
        'end_date',
        'objective',
        'notes',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'status' => TrainingWeekStatus::class,
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(TrainingPlan::class, 'training_plan_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class)->orderBy('scheduled_date')->orderBy('sort_order');
    }
}
