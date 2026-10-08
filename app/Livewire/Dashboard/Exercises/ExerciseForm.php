<?php

namespace App\Livewire\Dashboard\Exercises;

use App\Enums\ExerciseDifficulty;
use App\Http\Requests\StoreExerciseRequest;
use App\Http\Requests\UpdateExerciseRequest;
use App\Models\Exercise;
use App\Models\Sport;
use App\Services\ExerciseService;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Formulário único de criação/edição de exercício.
 * As rotas `exercises.create` e `exercises.edit` apontam para este componente.
 */
#[Layout('components.layouts.app')]
class ExerciseForm extends Component
{
    use ValidatesWithFormRequest, WithToastr;

    public $exerciseId = null;

    public $sport_id = '';

    public $name = '';

    public $description = '';

    public $instructions = '';

    public $video_url = '';

    public $difficulty = '';

    public bool $active = true;

    public function mount($exercise = null): void
    {
        if (is_string($exercise) || is_int($exercise)) {
            // Escopo de posse: exercício de outro professor → 404.
            $exercise = $this->baseQuery()->find($exercise) ?? abort(404);
        }

        if ($exercise instanceof Exercise) {
            Gate::authorize('update', $exercise);

            $this->exerciseId = $exercise->id;
            $this->sport_id = $exercise->sport_id;
            $this->name = $exercise->name;
            $this->description = $exercise->description;
            $this->instructions = $exercise->instructions;
            $this->video_url = $exercise->video_url;
            $this->difficulty = $exercise->difficulty?->value;
            $this->active = $exercise->active;

            return;
        }

        Gate::authorize('create', Exercise::class);
    }

    public function save(ExerciseService $service)
    {
        $data = [
            'exercise_id' => $this->exerciseId, // usado só pela regra unique (ignora o próprio registro)
            'sport_id' => $this->sport_id,
            'name' => $this->name,
            'description' => $this->description ?: null,
            'instructions' => $this->instructions ?: null,
            'video_url' => $this->video_url ?: null,
            'difficulty' => $this->difficulty ?: null,
            'active' => $this->active,
        ];

        if ($this->exerciseId) {
            $exercise = $this->baseQuery()->find($this->exerciseId) ?? abort(404);
            Gate::authorize('update', $exercise);

            $validated = $this->validateWith(UpdateExerciseRequest::class, $data);

            $service->update($exercise, $validated);

            session()->flash('toast', [
                'type' => 'success',
                'message' => 'Exercício atualizado com sucesso.',
            ]);

            return $this->redirectRoute('exercises.index');
        }

        Gate::authorize('create', Exercise::class);

        $validated = $this->validateWith(StoreExerciseRequest::class, $data);

        $service->store(auth()->user(), $validated);

        session()->flash('toast', [
            'type' => 'success',
            'message' => 'Exercício cadastrado com sucesso.',
        ]);

        return $this->redirectRoute('exercises.index');
    }

    public function cancel()
    {
        return $this->redirectRoute('exercises.index');
    }

    #[Title('Formulário de Exercício')]
    public function render()
    {
        return view('livewire.dashboard.exercises.form', [
            'sports' => Sport::active()->orderBy('name')->get(),
            'difficultyLabels' => ExerciseDifficulty::labels(),
            'isEdit' => (bool) $this->exerciseId,
        ]);
    }

    /**
     * Query base de posse (admin = tudo; professor = próprios + globais).
     */
    private function baseQuery()
    {
        $user = auth()->user();

        if ($user->isPlatformAdmin()) {
            return Exercise::query();
        }

        return Exercise::query()->ownedOrGlobal($user->resolveTenantId());
    }
}
