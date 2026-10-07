<?php

namespace App\Livewire\Dashboard\Students;

use App\Models\Student;
use App\Services\StudentService;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

class StudentIndex extends Component
{
    use WithPagination, WithToastr;

    protected $paginationTheme = 'bootstrap';

    public string $search = '';

    public $deleteId = null;

    public function mount(): void
    {
        Gate::authorize('viewAny', Student::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete($studentId): void
    {
        Gate::authorize('delete', Student::find($studentId));

        $this->deleteId = $studentId;
    }

    public function delete(StudentService $service): void
    {
        $student = Student::find($this->deleteId);

        if ($student) {
            Gate::authorize('delete', $student);
            $service->delete($student);
            $this->toastSuccess('Aluno excluído com sucesso.');
        }

        $this->deleteId = null;
    }

    public function toggleActive(Student $student, StudentService $service): void
    {
        Gate::authorize('update', $student);

        $service->update($student, ['active' => ! $student->active]);

        $this->toastSuccess($student->active ? 'Aluno ativado.' : 'Aluno desativado.');
    }

    #[Title('Alunos')]
    public function render()
    {
        $students = Student::query()
            ->with('user')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'LIKE', "%{$this->search}%")
                        ->orWhere('email', 'LIKE', "%{$this->search}%")
                        ->orWhere('phone', 'LIKE', "%{$this->search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.dashboard.students.index', [
            'students' => $students,
        ]);
    }
}
