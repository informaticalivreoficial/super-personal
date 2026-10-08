<?php

namespace App\Livewire\Dashboard\Plans;

use App\Enums\SessionItemType;
use App\Http\Requests\StoreSessionItemRequest;
use App\Models\Exercise;
use App\Models\TrainingSession;
use App\Services\TrainingSessionService;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Composição do treino: CRUD dos itens de uma sessão (nested no PlanShow).
 * Tenant: a sessão é resolvida pelo escopo global; itens são filhos dela.
 */
class SessionItems extends Component
{
    use ValidatesWithFormRequest, WithToastr;

    public TrainingSession $session;

    public bool $showForm = false;

    public $editingItemId = null;

    public $item = [
        'type' => '',
        'title' => '',
        'description' => '',
        'exercise_id' => '',
        'duration' => '',
        'distance' => '',
        'repetitions' => '',
        'sets' => '',
        'rest' => '',
        'target' => '',
        'intensity' => '',
    ];

    /**
     * @param  TrainingSession|string|int|null  $session  model (teste), id da rota ou null.
     *                                                    O tipo não é declarado de propósito (mesmo padrão do PlanShow):
     *                                                    com `TrainingSession` o container do Livewire instancia um
     *                                                    model vazio quando o parâmetro não vem de forma explícita.
     */
    public function mount($session = null): void
    {
        if (is_string($session) || is_int($session)) {
            // Escopo do tenant aplicado: id de outro professor → null → 404.
            $session = TrainingSession::find($session) ?? abort(404);
        }

        abort_unless($session instanceof TrainingSession, 404);

        Gate::authorize('view', $session);

        $this->session = $session;
    }

    public function openForm(): void
    {
        Gate::authorize('update', $this->session);

        $this->resetItemForm();
        $this->showForm = true;
    }

    public function editItem($itemId): void
    {
        Gate::authorize('update', $this->session);

        $item = $this->session->items()->find($itemId) ?? abort(404);

        $this->resetItemForm();
        $this->editingItemId = $item->id;
        $this->item = [
            'type' => $item->type?->value ?? '',
            'title' => $item->title,
            'description' => $item->description,
            'exercise_id' => $item->exercise_id,
            'duration' => $item->duration,
            'distance' => $item->distance,
            'repetitions' => $item->repetitions,
            'sets' => $item->sets,
            'rest' => $item->rest,
            'target' => $item->target,
            'intensity' => $item->intensity,
        ];
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->resetItemForm();
    }

    public function saveItem(TrainingSessionService $service): void
    {
        Gate::authorize('update', $this->session);

        $validated = $this->validateWith(
            StoreSessionItemRequest::class,
            $this->itemData(),
            'item'
        );

        if ($this->editingItemId) {
            $item = $this->session->items()->find($this->editingItemId) ?? abort(404);
            $service->updateItem($item, $validated);
            $this->toastSuccess('Item atualizado com sucesso.');
        } else {
            $service->storeItem($this->session, $validated);
            $this->toastSuccess('Item adicionado com sucesso.');
        }

        $this->resetItemForm();
    }

    public function deleteItem($itemId, TrainingSessionService $service): void
    {
        Gate::authorize('update', $this->session);

        $item = $this->session->items()->find($itemId) ?? abort(404);

        $service->deleteItem($item);

        if ($this->editingItemId === $itemId) {
            $this->resetItemForm();
        }

        $this->toastSuccess('Item excluído com sucesso.');
    }

    public function moveItem($itemId, int $direction, TrainingSessionService $service): void
    {
        Gate::authorize('update', $this->session);

        $item = $this->session->items()->find($itemId) ?? abort(404);

        $service->moveItem($item, $direction < 0 ? -1 : 1);
    }

    // *********************** Helpers **********************************************/

    private function resetItemForm(): void
    {
        $this->showForm = false;
        $this->editingItemId = null;
        $this->item = [
            'type' => '',
            'title' => '',
            'description' => '',
            'exercise_id' => '',
            'duration' => '',
            'distance' => '',
            'repetitions' => '',
            'sets' => '',
            'rest' => '',
            'target' => '',
            'intensity' => '',
        ];
        $this->resetValidation();
    }

    /**
     * Normaliza '' → null antes da validação (mesmo padrão do PlanShow).
     *
     * @return array<string, mixed>
     */
    private function itemData(): array
    {
        $normalize = fn ($value) => $value === '' ? null : $value;
        $normalizeInt = fn ($value) => $value !== '' && $value !== null ? (int) $value : null;

        return [
            'type' => $this->item['type'],
            'title' => $this->item['title'],
            'description' => $normalize($this->item['description']),
            'exercise_id' => $normalizeInt($this->item['exercise_id']),
            'duration' => $normalizeInt($this->item['duration']),
            'distance' => $normalizeInt($this->item['distance']),
            'repetitions' => $normalizeInt($this->item['repetitions']),
            'sets' => $normalizeInt($this->item['sets']),
            'rest' => $normalizeInt($this->item['rest']),
            'target' => $normalize($this->item['target']),
            'intensity' => $normalize($this->item['intensity']),
        ];
    }

    #[Title('Composição do Treino')]
    public function render()
    {
        $this->session->load('items.exercise');

        $teacherId = auth()->user()?->resolveTenantId();

        return view('livewire.dashboard.plans.session-items', [
            'items' => $this->session->items,
            'typeLabels' => SessionItemType::labels(),
            'exercises' => Exercise::query()
                ->active()
                ->ownedOrGlobal($teacherId)
                ->orderBy('name')
                ->get(),
        ]);
    }
}
