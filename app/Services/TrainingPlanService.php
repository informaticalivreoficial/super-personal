<?php

namespace App\Services;

use App\Enums\TrainingPlanStatus;
use App\Enums\TrainingSessionStatus;
use App\Enums\TrainingWeekStatus;
use App\Events\TrainingPlanPublished;
use App\Models\Student;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;
use App\Models\TrainingWeek;
use Illuminate\Support\Facades\DB;

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

    /**
     * Duplica um plano inteiro (semanas → sessões → composição) para outro
     * aluno — em regra de outro plano, mas aceita o mesmo.
     *
     * O novo plano nasce como RASCUNHO: publicar é ação explícita do treinador
     * (e é só a virada para ACTIVE que avisa o aluno, ver TrainingPlanPublished).
     * As datas são copiadas do plano de origem — o objetivo é reaproveitar a
     * periodização; ajustar o calendário do novo aluno é um passo à parte.
     */
    public function duplicate(TrainingPlan $source, Student $target, ?string $name = null): TrainingPlan
    {
        return DB::transaction(function () use ($source, $target, $name) {
            $source->loadMissing(['weeks.sessions.items']);

            $plan = new TrainingPlan;
            $plan->teacher_id = $target->teacher_id;
            $plan->student_id = $target->id;
            $plan->fill([
                'name' => $name ?: $source->name.' (cópia)',
                'description' => $source->description,
                'goal' => $source->goal,
                'start_date' => $source->start_date,
                'end_date' => $source->end_date,
                'notes' => $source->notes,
                'status' => TrainingPlanStatus::DRAFT,
            ]);
            $plan->save();

            foreach ($source->weeks as $sourceWeek) {
                $week = $this->copyWeek($sourceWeek, $plan, $sourceWeek->week_number, 0);

                foreach ($sourceWeek->sessions as $sourceSession) {
                    $this->copySession($sourceSession, $week, $plan, 0);
                }
            }

            return $plan;
        });
    }

    /**
     * Duplica uma semana para o FIM do próprio plano (repete o microciclo
     * depois da última semana): recebe o próximo Nº, copia sessões/composição
     * e desloca as datas para logo após o fim do plano, mantendo a duração.
     */
    public function duplicateWeek(TrainingWeek $source): TrainingWeek
    {
        return DB::transaction(function () use ($source) {
            $plan = $source->plan;
            $source->loadMissing(['sessions.items']);

            $lastWeek = TrainingWeek::where('training_plan_id', $plan->id)
                ->orderByDesc('week_number')
                ->first();

            $newStart = $lastWeek?->end_date?->copy()->addDay() ?? $source->start_date?->copy();
            $shiftDays = ($source->start_date && $newStart)
                ? (int) $source->start_date->diffInDays($newStart)
                : 0;

            $week = $this->copyWeek(
                $source,
                $plan,
                (int) $lastWeek?->week_number + 1,
                $shiftDays
            );

            foreach ($source->sessions as $sourceSession) {
                $this->copySession($sourceSession, $week, $plan, $shiftDays);
            }

            return $week;
        });
    }

    /**
     * Cópia de uma semana para outro plano (ou outro ponto do mesmo plano).
     * Status sempre reiniciado: a cópia é um período novo, não o que já passou.
     */
    private function copyWeek(TrainingWeek $source, TrainingPlan $plan, int $weekNumber, int $shiftDays): TrainingWeek
    {
        $week = new TrainingWeek;
        $week->teacher_id = $plan->teacher_id;
        $week->training_plan_id = $plan->id;
        $week->week_number = $weekNumber;
        $week->name = $source->name;
        $week->start_date = $source->start_date?->copy()->addDays($shiftDays);
        $week->end_date = $source->end_date?->copy()->addDays($shiftDays);
        $week->objective = $source->objective;
        $week->notes = $source->notes;
        $week->status = TrainingWeekStatus::PENDING;
        $week->save();

        return $week;
    }

    /**
     * Cópia de uma sessão (com a composição) para a semana/plano de destino.
     * O aluno da cópia é o do plano de destino (não o da origem) e o status
     * volta para PLANEJADO — histórico de execução não é copiado.
     */
    private function copySession(
        TrainingSession $source,
        TrainingWeek $week,
        TrainingPlan $plan,
        int $shiftDays
    ): TrainingSession {
        $session = new TrainingSession;
        $session->teacher_id = $plan->teacher_id;
        $session->training_week_id = $week->id;
        $session->student_id = $plan->student_id;
        $session->fill([
            'sport_id' => $source->sport_id,
            'exercise_id' => $source->exercise_id,
            'title' => $source->title,
            'description' => $source->description,
            'scheduled_date' => $source->scheduled_date?->copy()->addDays($shiftDays),
            'estimated_duration' => $source->estimated_duration,
            'distance' => $source->distance,
            'intensity' => $source->intensity,
            'target_pace' => $source->target_pace,
            'target_heart_rate' => $source->target_heart_rate,
            'target_power' => $source->target_power,
            'instructions' => $source->instructions,
            'coach_notes' => $source->coach_notes,
            'status' => TrainingSessionStatus::PLANNED,
            'sort_order' => $source->sort_order,
        ]);
        $session->save();

        foreach ($source->items as $sourceItem) {
            $session->items()->create([
                'exercise_id' => $sourceItem->exercise_id,
                'type' => $sourceItem->type,
                'title' => $sourceItem->title,
                'description' => $sourceItem->description,
                'duration' => $sourceItem->duration,
                'distance' => $sourceItem->distance,
                'repetitions' => $sourceItem->repetitions,
                'sets' => $sourceItem->sets,
                'rest' => $sourceItem->rest,
                'target' => $sourceItem->target,
                'intensity' => $sourceItem->intensity,
                'sort_order' => $sourceItem->sort_order,
            ]);
        }

        return $session;
    }
}
