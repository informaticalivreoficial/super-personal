<?php

namespace App\Listeners;

use App\Events\TrainingPlanPublished;
use App\Notifications\NewTrainingAvailable;

/**
 * Avisa o aluno que o plano está disponível (canal database;
 * push Android futuro no via()).
 */
class SendNewTrainingAvailableNotification
{
    public function handle(TrainingPlanPublished $event): void
    {
        $plan = $event->plan;

        $user = $plan->student?->user;

        // Aluno sem conta ou com login bloqueado → não notifica.
        if (! $user || $user->status != 1) {
            return;
        }

        $user->notify(new NewTrainingAvailable([
            'title' => 'Novo treino disponível',
            'message' => "O plano \"{$plan->name}\" está disponível para você começar.",
            'plan_id' => $plan->id,
            'student_id' => $plan->student_id,
            'teacher_id' => $plan->teacher_id,
            'status' => $plan->status->value,
        ]));
    }
}
