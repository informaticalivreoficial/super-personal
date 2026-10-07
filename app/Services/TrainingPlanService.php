<?php

namespace App\Services;

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

        return $plan;
    }

    public function update(TrainingPlan $plan, array $data): TrainingPlan
    {
        $plan->fill($data);
        $plan->save();

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
}
