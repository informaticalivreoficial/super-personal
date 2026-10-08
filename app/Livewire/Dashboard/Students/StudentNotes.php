<?php

namespace App\Livewire\Dashboard\Students;

use App\Enums\NoteVisibility;
use App\Http\Requests\StoreStudentNoteRequest;
use App\Models\Student;
use App\Models\StudentNote;
use App\Services\StudentNoteService;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Observações do professor sobre o aluno (aninhado no StudentShow).
 * visibility = private nunca aparece para o aluno.
 */
class StudentNotes extends Component
{
    use ValidatesWithFormRequest, WithPagination, WithToastr;

    protected $paginationTheme = 'tailwind';

    public Student $student;

    public bool $showForm = false;

    public $content;

    public $visibility = NoteVisibility::PRIVATE->value;

    public function mount(Student $student): void
    {
        Gate::authorize('view', $student);

        $this->student = $student;
    }

    public function toggleForm(): void
    {
        $this->showForm = ! $this->showForm;
    }

    public function saveNote(StudentNoteService $service): void
    {
        Gate::authorize('create', [StudentNote::class, $this->student]);

        $validated = $this->validateWith(StoreStudentNoteRequest::class, [
            'content' => $this->content,
            'visibility' => $this->visibility,
        ]);

        $service->store($this->student, $validated);

        $this->toastSuccess('Observação registrada com sucesso.');
        $this->reset(['content']);
        $this->visibility = NoteVisibility::PRIVATE->value;
        $this->resetValidation();
        $this->resetPage('notas');
        $this->showForm = false;
    }

    public function deleteNote($noteId, StudentNoteService $service): void
    {
        $note = $this->student->notes()->find($noteId) ?? abort(404);

        Gate::authorize('delete', $note);

        $service->delete($note);

        $this->toastSuccess('Observação excluída.');
    }

    public function render()
    {
        return view('livewire.dashboard.students.notes', [
            'notes' => $this->student->notes()
                ->paginate(10, ['*'], 'notas'),
            'visibilityLabels' => NoteVisibility::labels(),
        ]);
    }
}
