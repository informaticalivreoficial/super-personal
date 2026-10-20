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
use Illuminate\Support\Facades\Storage;

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

    /**
     * URL pública da foto de perfil (caminho salvo no disco `public`).
     * Aceita também URLs cheias (fotos legadas/importadas).
     */
    public function profilePhotoUrl(): ?string
    {
        if (empty($this->profile_photo)) {
            return null;
        }

        if (str_starts_with($this->profile_photo, 'http') || str_starts_with($this->profile_photo, '/')) {
            return $this->profile_photo;
        }

        return Storage::disk('public')->url($this->profile_photo);
    }

    /** Iniciais para o avatar quando não há foto ("Renato Silva" → "RS"). */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return '?';
        }

        $first = mb_strtoupper(mb_substr($parts[0], 0, 1));
        $last = count($parts) > 1 ? mb_strtoupper(mb_substr($parts[count($parts) - 1], 0, 1)) : '';

        return $first.$last;
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
