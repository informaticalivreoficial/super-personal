<?php

namespace App\Livewire\Dashboard\Notifications;

use App\Models\Student;
use App\Services\StudentNotificationService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Auditoria das notificações enviadas aos alunos: mensagens do treinador,
 * planos publicados e os lembretes automáticos (cobrança/treino) — com
 * filtros por aluno, tipo e situação de leitura.
 *
 * Somente leitura: o envio continua na página de mensagens do aluno.
 */
#[Layout('components.layouts.app')]
#[Title('Notificações Enviadas')]
class NotificationIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public $filterStudentId = null;

    public string $filterType = '';

    public string $filterStatus = '';

    public string $from = '';

    public string $to = '';

    public function mount(): void
    {
        // A página é do treinador (admin não tem alunos/notificações próprias).
        Gate::authorize('viewAny', Student::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStudentId(): void
    {
        $this->resetPage();
    }

    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFrom(): void
    {
        $this->resetPage();
    }

    public function updatedTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterStudentId = null;
        $this->filterType = '';
        $this->filterStatus = '';
        $this->from = '';
        $this->to = '';

        $this->resetPage();
    }

    public function render(StudentNotificationService $service)
    {
        $filters = $service->filters([
            'search' => $this->search,
            'student_id' => $this->filterStudentId,
            'type' => $this->filterType,
            'status' => $this->filterStatus,
            'from' => $this->from,
            'to' => $this->to,
        ]);

        return view('livewire.dashboard.notifications.index', [
            'filters' => $filters,
            'notifications' => $service->paginate($filters),
            'counts' => $service->counts($filters),
            'typeCounts' => $service->typeCounts($filters),
            'studentOptions' => $service->studentOptions(),
            'types' => StudentNotificationService::TYPES,
            'statuses' => StudentNotificationService::STATUS_OPTIONS,
        ]);
    }
}
