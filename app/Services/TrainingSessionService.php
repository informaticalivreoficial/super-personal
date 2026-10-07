<?php

namespace App\Services;

use App\Models\TrainingSession;
use App\Models\TrainingSessionItem;
use App\Models\TrainingWeek;
use Illuminate\Support\Facades\DB;

class TrainingSessionService
{
    public function store(TrainingWeek $week, array $data): TrainingSession
    {
        return DB::transaction(function () use ($week, $data) {
            $plan = $week->plan;

            $session = new TrainingSession;
            $session->teacher_id = $week->teacher_id;
            $session->training_week_id = $week->id;
            $session->student_id = $plan->student_id;
            $session->fill($this->filter($data));
            $session->save();

            $this->syncItems($session, $data['items'] ?? []);

            return $session->load('items');
        });
    }

    public function update(TrainingSession $session, array $data): TrainingSession
    {
        return DB::transaction(function () use ($session, $data) {
            $session->fill($this->filter($data));
            $session->save();

            if (array_key_exists('items', $data)) {
                // Estratégia simples e previsível: recria os itens enviados.
                $session->items()->delete();
                $this->syncItems($session, $data['items'] ?? []);
            }

            return $session->load('items');
        });
    }

    public function delete(TrainingSession $session): void
    {
        $session->delete();
    }

    /**
     * Remove chaves que não são preenchíveis no model (ex.: itens).
     *
     * @return array<string, mixed>
     */
    private function filter(array $data): array
    {
        return collect($data)
            ->except(['items', 'training_week_id', 'student_id', 'teacher_id', 'status'])
            ->all();
    }

    private function syncItems(TrainingSession $session, array $items): void
    {
        foreach (array_values($items) as $index => $item) {
            $model = new TrainingSessionItem;
            $model->training_session_id = $session->id;
            $model->fill(collect($item)->except(['training_session_id', 'sort_order'])->all());
            $model->sort_order = $item['sort_order'] ?? $index + 1;
            $model->save();
        }
    }
}
