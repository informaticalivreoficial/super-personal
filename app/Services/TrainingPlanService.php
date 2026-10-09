<?php

namespace App\Services;

use App\Enums\TrainingPlanStatus;
use App\Events\TrainingPlanPublished;
use App\Models\Student;
use App\Models\TrainingPlan;
use App\Models\TrainingWeek;

class TrainingPlanService
{
    public function store(Student $student, array $data): TrainingPlan
    {
        $plan = new TrainingPlan;
        $plan->teacher_id = $student->teacher_id;
        $plan->student_id = $student->id;
        $plan->fill($data);
        $plan->save();

        // Plano já publicado no cadastro → aluno avisado.
        if ($plan->status === TrainingPlanStatus::ACTIVE) {
            event(new TrainingPlanPublished($plan));
        }

        return $plan;
    }

    public function update(TrainingPlan $plan, array $data): TrainingPlan
    {
        $wasActive = $plan->status === TrainingPlanStatus::ACTIVE;

        $plan->fill($data);
        $plan->save();

        // Transição rascunho/outro → ativo = publicação (só nessa virada
        // para não reavisar a cada edição de um plano já ativo).
        if (! $wasActive && $plan->status === TrainingPlanStatus::ACTIVE) {
            event(new TrainingPlanPublished($plan));
        }

        return $plan;
    }

    public function delete(TrainingPlan $plan): void
    {
        // Semanas, sessões, itens e execuções caem em cascata (FK cascadeOnDelete).
        $plan->delete();
    }

    public function storeWeek(TrainingPlan $plan, array $data): TrainingWeek
    {
        $week = new TrainingWeek;
        $week->teacher_id = $plan->teacher_id;
        $week->training_plan_id = $plan->id;
        $week->fill($data);
        $week->save();

        return $week;
    }

    public function updateWeek(TrainingWeek $week, array $data): TrainingWeek
    {
        $week->fill($data);
        $week->save();

        return $week;
    }

    /**
     * Sessões caem em cascata (FK cascadeOnDelete em training_sessions.training_week_id).
     */
    public function deleteWeek(TrainingWeek $week): void
    {
        $week->delete();
    }
}
