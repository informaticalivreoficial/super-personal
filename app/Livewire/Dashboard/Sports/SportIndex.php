<?php

namespace App\Livewire\Dashboard\Sports;

use App\Models\Sport;
use App\Services\SportService;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Lista de modalidades (catálogo global — gestão exclusiva do admin).
 */
class SportIndex extends Component
{
    use WithPagination, WithToastr;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public $deleteId = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Sport::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete($sportId): void
    {
        $sport = Sport::find($sportId) ?? abort(404);
        Gate::authorize('delete', $sport);

        $this->deleteId = $sport->id;
    }

    public function delete(SportService $service): void
    {
        $sport = Sport::find($this->deleteId);

        if ($sport) {
            Gate::authorize('delete', $sport);

            try {
                $service->delete($sport);
                $this->toastSuccess('Modalidade excluída com sucesso.');
            } catch (ValidationException $e) {
                $this->toastError($e->getMessage());
            }
        }

        $this->deleteId = null;
    }

    #[Title('Modalidades')]
    public function render()
    {
        $sports = Sport::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'LIKE', "%{$this->search}%")
                        ->orWhere('description', 'LIKE', "%{$this->search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.dashboard.sports.index', [
            'sports' => $sports,
            'canManage' => Gate::allows('create', Sport::class),
        ]);
    }
}
