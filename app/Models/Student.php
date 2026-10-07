<?php

namespace App\Models;

use App\Enums\StudentGender;
use App\Enums\StudentLevel;
use App\Traits\BelongsToTeacher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use BelongsToTeacher, HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'birth_date',
        'gender',
        'document',
        'profile_photo',
        'height',
        'initial_weight',
        'current_weight',
        'target_weight',
        'goal',
        'fitness_level',
        'training_experience',
        'available_days',
        'observations',
        'active',
        'started_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'gender' => StudentGender::class,
        'fitness_level' => StudentLevel::class,
        'available_days' => 'array',
        'active' => 'boolean',
        'started_at' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trainingPlans(): HasMany
    {
        return $this->hasMany(TrainingPlan::class)->orderBy('start_date');
    }

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(TrainingExecution::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(StudentProgress::class)->orderByDesc('recorded_at');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(StudentNote::class)->orderByDesc('created_at');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
