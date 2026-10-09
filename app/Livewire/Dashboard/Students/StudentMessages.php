<?php

namespace App\Livewire\Dashboard\Students;

use App\Http\Requests\SendMessageRequest;
use App\Models\Student;
use App\Notifications\TeacherMessage;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Mensagens do treinador ao aluno (aninhado no StudentShow).
 * Envia notificação database (canal único por enquanto; push Android no futuro)
 * — a lista do aluno é a API GET /api/v1/student/notifications.
 */
class StudentMessages extends Component
{
    use ValidatesWithFormRequest, WithToastr;

    public Student $student;

    public bool $showForm = false;

    public $title;

    public $message;

    public function mount(Student $student): void
    {
        Gate::authorize('view', $student);

        $this->student = $student;
    }

    public function toggleForm(): void
    {
        $this->showForm = ! $this->showForm;
    }

    public function sendMessage(): void
    {
        Gate::authorize('update', $this->student);

        $validated = $this->validateWith(SendMessageRequest::class, [
            'title' => $this->title,
            'message' => $this->message,
        ]);

        $user = $this->student->user;

        if (! $user) {
            throw ValidationException::withMessages([
                'message' => 'Aluno sem acesso ao aplicativo: não há usuário vinculado.',
            ]);
        }

        $user->notify(new TeacherMessage([
            'title' => $validated['title'],
            'message' => $validated['message'],
            'student_id' => $this->student->id,
            'teacher_id' => $this->student->teacher_id,
            'sent_by' => auth()->id(),
        ]));

        $this->toastSuccess('Mensagem enviada ao aluno.');
        $this->reset(['title', 'message']);
        $this->resetValidation();
        $this->showForm = false;
    }

    public function render()
    {
        $user = $this->student->user;

        return view('livewire.dashboard.students.messages', [
            'hasAppAccess' => (bool) $user,
            'sentMessages' => $user
                ? $user->notifications()
                    ->where('type', TeacherMessage::class)
                    ->latest()
                    ->limit(10)
                    ->get()
                : collect(),
        ]);
    }
}
