<?php

namespace App\Services;

use App\Enums\SessionItemType;
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

    // *********************** Itens da sessão **************************************/

    /**
     * Adiciona um item ao fim da composição da sessão.
     */
    public function storeItem(TrainingSession $session, array $data): TrainingSessionItem
    {
        return DB::transaction(function () use ($session, $data) {
            $nextOrder = (int) $session->items()->max('sort_order') + 1;

            $item = new TrainingSessionItem;
            $item->training_session_id = $session->id;
            $item->fill($this->filterItem($data));
            $item->type = $data['type'] ?? SessionItemType::WORK;
            $item->sort_order = $nextOrder;
            $item->save();

            return $item;
        });
    }

    public function updateItem(TrainingSessionItem $item, array $data): TrainingSessionItem
    {
        return DB::transaction(function () use ($item, $data) {
            $item->fill($this->filterItem($data));

            if (array_key_exists('type', $data)) {
                $item->type = $data['type'];
            }

            $item->save();

            return $item;
        });
    }

    public function deleteItem(TrainingSessionItem $item): void
    {
        DB::transaction(function () use ($item) {
            $session = $item->session;
            $item->delete();
            $this->renumberItems($session);
        });
    }

    /**
     * Move o item uma posição para cima (-1) ou para baixo (+1).
     */
    public function moveItem(TrainingSessionItem $item, int $direction): void
    {
        DB::transaction(function () use ($item, $direction) {
            $items = $item->session->items()->get()->values();
            $index = $items->search(fn (TrainingSessionItem $candidate) => $candidate->id === $item->id);

            if ($index === false) {
                return;
            }

            $target = $index + $direction;

            if ($target < 0 || $target >= $items->count()) {
                return;
            }

            $items->splice($index, 1);
            $items->splice($target, 0, [$item]);

            foreach ($items as $position => $candidate) {
                if ($candidate->sort_order !== $position + 1) {
                    $candidate->sort_order = $position + 1;
                    $candidate->save();
                }
            }
        });
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

    /**
     * Chaves preenchíveis de um item (ids/ordem são responsabilidade do service).
     *
     * @return array<string, mixed>
     */
    private function filterItem(array $data): array
    {
        return collect($data)
            ->except(['training_session_id', 'sort_order', 'type'])
            ->all();
    }

    private function syncItems(TrainingSession $session, array $items): void
    {
        foreach (array_values($items) as $index => $item) {
            $model = new TrainingSessionItem;
            $model->training_session_id = $session->id;
            $model->fill(collect($item)->except(['training_session_id', 'sort_order'])->all());
            $model->type = $item['type'] ?? SessionItemType::WORK;
            $model->sort_order = $item['sort_order'] ?? $index + 1;
            $model->save();
        }
    }

    /**
     * Renumera 1..N após remoção (mantém a ordem estável para a UI).
     */
    private function renumberItems(TrainingSession $session): void
    {
        $session->items()->get()->each(function (TrainingSessionItem $item, int $index) {
            if ($item->sort_order !== $index + 1) {
                $item->sort_order = $index + 1;
                $item->save();
            }
        });
    }
}
