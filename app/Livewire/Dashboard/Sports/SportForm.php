<?php

namespace App\Livewire\Dashboard\Sports;

use App\Http\Requests\StoreSportRequest;
use App\Http\Requests\UpdateSportRequest;
use App\Models\Sport;
use App\Services\SportService;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Formulário único de criação/edição de modalidade.
 * As rotas `sports.create` e `sports.edit` apontam para este componente.
 */
#[Layout('components.layouts.app')]
class SportForm extends Component
{
    use ValidatesWithFormRequest, WithToastr;

    public $sportId = null;

    public $name = '';

    public $description = '';

    public $icon = '';

    public bool $active = true;

    public function mount($sport = null): void
    {
        if (is_string($sport) || is_int($sport)) {
            $sport = Sport::find($sport) ?? abort(404);
        }

        if ($sport instanceof Sport) {
            Gate::authorize('update', $sport);

            $this->sportId = $sport->id;
            $this->name = $sport->name;
            $this->description = $sport->description;
            $this->icon = $sport->icon;
            $this->active = $sport->active;

            return;
        }

        Gate::authorize('create', Sport::class);
    }

    public function save(SportService $service)
    {
        $data = [
            'sport_id' => $this->sportId,
            'name' => $this->name,
            'description' => $this->description ?: null,
            'icon' => $this->icon ?: null,
            'active' => $this->active,
        ];

        if ($this->sportId) {
            $sport = Sport::find($this->sportId) ?? abort(404);
            Gate::authorize('update', $sport);

            $validated = $this->validateWith(UpdateSportRequest::class, $data);

            $service->update($sport, $validated);

            session()->flash('toast', [
                'type' => 'success',
                'message' => 'Modalidade atualizada com sucesso.',
            ]);

            return $this->redirectRoute('sports.index');
        }

        Gate::authorize('create', Sport::class);

        $validated = $this->validateWith(StoreSportRequest::class, $data);

        $service->store($validated);

        session()->flash('toast', [
            'type' => 'success',
            'message' => 'Modalidade criada com sucesso.',
        ]);

        return $this->redirectRoute('sports.index');
    }

    public function cancel()
    {
        return $this->redirectRoute('sports.index');
    }

    #[Title('Formulário de Modalidade')]
    public function render()
    {
        return view('livewire.dashboard.sports.form', [
            'isEdit' => (bool) $this->sportId,
        ]);
    }
}
