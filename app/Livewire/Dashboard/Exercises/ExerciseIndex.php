<?php

namespace App\Livewire\Dashboard\Exercises;

use App\Enums\ExerciseDifficulty;
use App\Models\Exercise;
use App\Services\ExerciseService;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Biblioteca de exercícios: o treinador vê os próprios + os globais da
 * plataforma; só edita os próprios (ExercisePolicy). Admin vê/edita todos.
 */
class ExerciseIndex extends Component
{
    use WithPagination, WithToastr;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public $deleteId = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Exercise::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete($exerciseId): void
    {
        // Escopo de posse aplicado: exercício de outro treinador → 404.
        $exercise = $this->baseQuery()->find($exerciseId) ?? abort(404);
        Gate::authorize('delete', $exercise);

        $this->deleteId = $exercise->id;
    }

    public function delete(ExerciseService $service): void
    {
        $exercise = $this->baseQuery()->find($this->deleteId);

        if ($exercise) {
            Gate::authorize('delete', $exercise);
            $service->delete($exercise);
            $this->toastSuccess('Exercício excluído com sucesso.');
        }

        $this->deleteId = null;
    }

    #[Title('Exercícios')]
    public function render()
    {
        $exercises = $this->baseQuery()
            ->with('sport')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'LIKE', "%{$this->search}%")
                        ->orWhere('description', 'LIKE', "%{$this->search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.dashboard.exercises.index', [
            'exercises' => $exercises,
            'difficultyLabels' => ExerciseDifficulty::labels(),
        ]);
    }

    /**
     * Query base: admin enxerga tudo; treinador enxerga os próprios + globais.
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
