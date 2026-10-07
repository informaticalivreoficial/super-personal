<?php

namespace App\Models;

use App\Enums\SessionItemType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingSessionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'exercise_id',
        'type',
        'title',
        'description',
        'duration',
        'distance',
        'repetitions',
        'sets',
        'rest',
        'target',
        'intensity',
        'sort_order',
    ];

    protected $casts = [
        'type' => SessionItemType::class,
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }
}
