<?php

namespace App\Livewire\Dashboard\Professores;

use App\Models\Teacher;
use App\Services\TeacherService;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Lista de professores (tenants) da plataforma — gestão exclusiva do admin.
 */
class ProfessorIndex extends Component
{
    use WithPagination, WithToastr;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', Teacher::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $teacherId, TeacherService $service): void
    {
        $teacher = Teacher::find($teacherId) ?? abort(404);
        Gate::authorize('update', $teacher);

        $service->toggleActive($teacher);

        $this->toastSuccess(
            $teacher->active
                ? "Professor {$teacher->name} ativado."
                : "Professor {$teacher->name} desativado (login bloqueado)."
        );
    }

    #[Title('Professores')]
    public function render()
    {
        $teachers = Teacher::query()
            ->withCount('students')
            ->when($this->search !== '', function ($query) {
                $search = $this->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%"));
                });
            })
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.dashboard.professores.index', [
            'teachers' => $teachers,
        ]);
    }
}
