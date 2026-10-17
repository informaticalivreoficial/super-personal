<?php

namespace App\Livewire\Dashboard\Professores;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Models\Teacher;
use App\Services\TeacherService;
use App\Traits\WithToastr;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Lista de treinadores (tenants) da plataforma — gestão exclusiva do admin.
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
                ? "Treinador {$teacher->name} ativado."
                : "Treinador {$teacher->name} desativado (login bloqueado)."
        );
    }

    #[Title('Treinadores')]
    public function render()
    {
        $teachers = Teacher::query()
            ->withCount('students')
            // Saldo a repassar: líquido das cobranças online pagas ainda sem
            // repasse (a plataforma não cobra assinatura — a receita é a
            // comissão retida em cada cobrança, o líquido é do treinador).
            ->withSum(['payments as awaiting_payout_amount' => fn ($query) => $query
                ->where('channel', PaymentChannel::ONLINE)
                ->where('status', PaymentStatus::PAID)
                ->whereNull('payout_id')], 'net_amount')
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
