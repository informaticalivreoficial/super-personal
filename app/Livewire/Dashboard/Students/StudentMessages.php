<?php

namespace App\Livewire\Dashboard\Students;

use App\Http\Requests\SendMessageRequest;
use App\Models\Student;
use App\Services\StudentMessageService;
use App\Traits\ValidatesWithFormRequest;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Mensagens do treinador ao aluno (página dedicada, saída da ficha do aluno).
 * Envia notificação database para o usuário do aluno — a lista do aluno é a
 * API GET /api/v1/student/notifications.
 *
 * O modal de envio é Alpine (padrão do painel); o PHP só dispara os eventos
 * `open-message-modal` (abre já com o formulário limpo) e `message-sent`
 * (fecha depois do envio).
 */
#[Layout('components.layouts.app')]
#[Title('Mensagens do Aluno')]
class StudentMessages extends Component
{
    use ValidatesWithFormRequest, WithPagination, WithToastr;

    protected $paginationTheme = 'tailwind';

    public Student $student;

    public string $search = '';

    public string $status = '';

    public $title;

    public $message;

    public function mount(Student $student): void
    {
        Gate::authorize('view', $student);

        $this->student = $student;
    }

    public function updatingSearch(): void
    {
        $this->resetPage('mensagens');
    }

    public function updatedStatus(): void
    {
        $this->resetPage('mensagens');
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status']);
        $this->resetPage('mensagens');
    }

    /**
     * Abre o modal de envio com o formulário limpo.
     */
    public function openModal(): void
    {
        $this->reset(['title', 'message']);
        $this->resetValidation();

        $this->dispatch('open-message-modal');
    }

    public function sendMessage(StudentMessageService $service): void
    {
        Gate::authorize('update', $this->student);

        $validated = $this->validateWith(SendMessageRequest::class, [
            'title' => $this->title,
            'message' => $this->message,
        ]);

        $service->send($this->student, $validated);

        $this->toastSuccess('Mensagem enviada ao aluno.');
        $this->reset(['title', 'message']);
        $this->resetValidation();
        $this->resetPage('mensagens');

        $this->dispatch('message-sent');
    }

    public function render(StudentMessageService $service)
    {
        $hasAppAccess = $service->hasAppAccess($this->student);

        return view('livewire.dashboard.students.messages', [
            'hasAppAccess' => $hasAppAccess,
            'messages' => $hasAppAccess
                ? $service->paginate($this->student, ['search' => $this->search, 'status' => $this->status])
                : null,
            'counts' => $service->counts($this->student),
            'statusOptions' => StudentMessageService::STATUS_OPTIONS,
        ]);
    }
}
