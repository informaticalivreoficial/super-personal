<?php

namespace App\Services;

use App\Enums\TrainingPlanStatus;
use App\Enums\TrainingSessionStatus;
use App\Enums\TrainingWeekStatus;
use App\Events\TrainingPlanPublished;
use App\Models\Student;
use App\Models\TrainingPlan;
use App\Models\TrainingPlanTemplate;
use App\Models\TrainingSession;
use App\Models\TrainingWeek;
use Illuminate\Support\Carbon;
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
     * Salva a periodização de um plano como modelo reaplicável.
     *
     * As datas viram offsets em dias a partir do início do plano, então o
     * modelo pode ser aplicado a outro aluno/outra data sem perder a
     * progressão entre semanas e sessões.
     */
    public function saveAsTemplate(TrainingPlan $source, array $data): TrainingPlanTemplate
    {
        $source->loadMissing(['weeks.sessions.items']);

        $base = $source->start_date?->copy()->startOfDay() ?? now()->startOfDay();
        $structure = ['weeks' => []];
        $sessionsCount = 0;
        $sportIds = [];

        foreach ($source->weeks as $sourceWeek) {
            $weekStartOffset = $this->offsetFrom($base, $sourceWeek->start_date);
            $weekEndOffset = $this->offsetFrom($base, $sourceWeek->end_date);
            $sessions = [];

            foreach ($sourceWeek->sessions as $sourceSession) {
                $sessionsCount++;

                if ($sourceSession->sport_id) {
                    $sportIds[$sourceSession->sport_id] = true;
                }

                $sessions[] = [
                    'sport_id' => $sourceSession->sport_id,
                    'exercise_id' => $sourceSession->exercise_id,
                    'title' => $sourceSession->title,
                    'description' => $sourceSession->description,
                    'scheduled_offset_days' => $this->offsetFrom($base, $sourceSession->scheduled_date),
                    'estimated_duration' => $sourceSession->estimated_duration,
                    'distance' => $sourceSession->distance,
                    'intensity' => $sourceSession->intensity,
                    'target_pace' => $sourceSession->target_pace,
                    'target_heart_rate' => $sourceSession->target_heart_rate,
                    'target_power' => $sourceSession->target_power,
                    'instructions' => $sourceSession->instructions,
                    'coach_notes' => $sourceSession->coach_notes,
                    'sort_order' => $sourceSession->sort_order,
                    'items' => $sourceSession->items->map(fn ($item) => [
                        'exercise_id' => $item->exercise_id,
                        'type' => $item->type?->value ?? 'work',
                        'title' => $item->title,
                        'description' => $item->description,
                        'duration' => $item->duration,
                        'distance' => $item->distance,
                        'repetitions' => $item->repetitions,
                        'sets' => $item->sets,
                        'rest' => $item->rest,
                        'target' => $item->target,
                        'intensity' => $item->intensity,
                        'sort_order' => $item->sort_order,
                    ])->all(),
                ];
            }

            $structure['weeks'][] = [
                'week_number' => $sourceWeek->week_number,
                'name' => $sourceWeek->name,
                'objective' => $sourceWeek->objective,
                'notes' => $sourceWeek->notes,
                'start_offset_days' => $weekStartOffset,
                'end_offset_days' => $weekEndOffset,
                'sessions' => $sessions,
            ];
        }

        $template = new TrainingPlanTemplate;
        $template->teacher_id = $source->teacher_id;
        $template->fill([
            'name' => $data['name'],
            'description' => $data['description'] ?? $source->description,
            'goal' => $source->goal,
            'sport_id' => $data['sport_id'] ?? (count($sportIds) === 1 ? array_key_first($sportIds) : null),
            'weeks_count' => count($structure['weeks']),
            'sessions_count' => $sessionsCount,
            'structure' => $structure,
        ]);
        $template->save();

        return $template;
    }

    /**
     * Aplica um modelo a um aluno, ancorando a periodização na data escolhida.
     *
     * O plano nasce como RASCUNHO (não notifica o aluno) e as datas das
     * semanas/sessões são recalculadas a partir dos offsets do modelo.
     */
    public function applyTemplate(
        TrainingPlanTemplate $template,
        Student $target,
        ?string $startDate = null,
        ?string $name = null
    ): TrainingPlan {
        return DB::transaction(function () use ($template, $target, $startDate, $name) {
            $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfDay();
            $weeks = $template->structure['weeks'] ?? [];
            $maxOffset = 0;

            $plan = new TrainingPlan;
            $plan->teacher_id = $target->teacher_id;
            $plan->student_id = $target->id;
            $plan->fill([
                'name' => $name ?: $template->name,
                'description' => $template->description,
                'goal' => $template->goal,
                'start_date' => $start->toDateString(),
                'end_date' => $start->toDateString(),
                'status' => TrainingPlanStatus::DRAFT,
            ]);
            $plan->save();

            foreach ($weeks as $index => $weekData) {
                $weekStartOffset = (int) ($weekData['start_offset_days'] ?? 0);
                $weekEndOffset = (int) ($weekData['end_offset_days'] ?? ($weekStartOffset + 6));
                $maxOffset = max($maxOffset, $weekEndOffset);

                $week = new TrainingWeek;
                $week->teacher_id = $plan->teacher_id;
                $week->training_plan_id = $plan->id;
                $week->week_number = (int) ($weekData['week_number'] ?? ($index + 1));
                $week->name = $weekData['name'] ?? null;
                $week->start_date = $start->copy()->addDays($weekStartOffset)->toDateString();
                $week->end_date = $start->copy()->addDays($weekEndOffset)->toDateString();
                $week->objective = $weekData['objective'] ?? null;
                $week->notes = $weekData['notes'] ?? null;
                $week->status = TrainingWeekStatus::PENDING;
                $week->save();

                foreach (($weekData['sessions'] ?? []) as $sessionData) {
                    $sessionOffset = (int) ($sessionData['scheduled_offset_days'] ?? $weekStartOffset);
                    $maxOffset = max($maxOffset, $sessionOffset);

                    $session = new TrainingSession;
                    $session->teacher_id = $plan->teacher_id;
                    $session->training_week_id = $week->id;
                    $session->student_id = $plan->student_id;
                    $session->fill([
                        'sport_id' => $sessionData['sport_id'] ?? null,
                        'exercise_id' => $sessionData['exercise_id'] ?? null,
                        'title' => $sessionData['title'] ?? 'Treino',
                        'description' => $sessionData['description'] ?? null,
                        'scheduled_date' => $start->copy()->addDays($sessionOffset)->toDateString(),
                        'estimated_duration' => $sessionData['estimated_duration'] ?? null,
                        'distance' => $sessionData['distance'] ?? null,
                        'intensity' => $sessionData['intensity'] ?? null,
                        'target_pace' => $sessionData['target_pace'] ?? null,
                        'target_heart_rate' => $sessionData['target_heart_rate'] ?? null,
                        'target_power' => $sessionData['target_power'] ?? null,
                        'instructions' => $sessionData['instructions'] ?? null,
                        'coach_notes' => $sessionData['coach_notes'] ?? null,
                        'status' => TrainingSessionStatus::PLANNED,
                        'sort_order' => $sessionData['sort_order'] ?? 0,
                    ]);
                    $session->save();

                    foreach (($sessionData['items'] ?? []) as $itemData) {
                        $session->items()->create([
                            'exercise_id' => $itemData['exercise_id'] ?? null,
                            'type' => $itemData['type'] ?? 'work',
                            'title' => $itemData['title'] ?? 'Item',
                            'description' => $itemData['description'] ?? null,
                            'duration' => $itemData['duration'] ?? null,
                            'distance' => $itemData['distance'] ?? null,
                            'repetitions' => $itemData['repetitions'] ?? null,
                            'sets' => $itemData['sets'] ?? null,
                            'rest' => $itemData['rest'] ?? null,
                            'target' => $itemData['target'] ?? null,
                            'intensity' => $itemData['intensity'] ?? null,
                            'sort_order' => $itemData['sort_order'] ?? 0,
                        ]);
                    }
                }
            }

            $plan->end_date = $start->copy()->addDays($maxOffset)->toDateString();
            $plan->save();

            return $plan;
        });
    }

    public function deleteTemplate(TrainingPlanTemplate $template): void
    {
        $template->delete();
    }

    private function offsetFrom(Carbon $base, ?Carbon $date): int
    {
        return $date ? (int) $base->diffInDays($date->copy()->startOfDay(), false) : 0;
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
