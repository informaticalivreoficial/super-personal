<?php

namespace App\Models;

use App\Enums\ExerciseDifficulty;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Biblioteca de exercícios/atividades (NÃO é o treino planejado).
 *
 * teacher_id NULL = exercício global (catálogo da plataforma).
 * Por isso este modelo NÃO usa o trait BelongsToTeacher:
 * o treinador deve ver os exercícios globais além dos próprios.
 * O isolamento de posse é garantido pela ExercisePolicy.
 */
class Exercise extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sport_id',
        'name',
        'slug',
        'description',
        'instructions',
        'video_url',
        'image',
        'difficulty',
        'active',
    ];

    protected $casts = [
        'difficulty' => ExerciseDifficulty::class,
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Exercise $exercise) {
            if (empty($exercise->slug)) {
                $exercise->slug = Str::slug($exercise->name);
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeOwnedOrGlobal(Builder $query, ?int $teacherId): Builder
    {
        return $query->where(function (Builder $q) use ($teacherId) {
            $q->whereNull('teacher_id')->orWhere('teacher_id', $teacherId);
        });
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function sessionItems(): HasMany
    {
        return $this->hasMany(TrainingSessionItem::class);
    }
}
