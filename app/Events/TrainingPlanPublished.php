<?php

namespace App\Events;

use App\Models\TrainingPlan;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Plano publicado: status transitou para ACTIVE (rascunho → ativo ou
 * reativação). O aluno já pode ver o plano na API/app.
 */
class TrainingPlanPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public TrainingPlan $plan) {}
}
