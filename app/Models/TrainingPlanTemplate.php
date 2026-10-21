<?php

namespace App\Models;

use App\Traits\BelongsToTeacher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo (template) de plano de treino do treinador.
 *
 * Guarda a periodização em `structure` como offsets de dias a partir do
 * início do plano, para ser aplicada a qualquer aluno/data sem carregar
 * as datas originais.
 */
class TrainingPlanTemplate extends Model
{
    use BelongsToTeacher;

    protected $fillable = [
        'name',
        'description',
        'goal',
        'sport_id',
        'weeks_count',
        'sessions_count',
        'structure',
    ];

    protected $casts = [
        'weeks_count' => 'integer',
        'sessions_count' => 'integer',
        'structure' => 'array',
    ];

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }
}
