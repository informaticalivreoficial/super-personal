<?php

namespace App\Livewire\Dashboard\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Traits\WithToastr;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Índice de assinaturas do SaaS (treinador ↔ plataforma) — admin only.
 * Escrita continua no card ProfessorSubscription (edição do treinador).
 */
#[Layout('components.layouts.app')]
class SubscriptionIndex extends Component
{
    use WithPagination, WithToastr;

    protected $paginationTheme = 'tailwind';

    public string $search = '';

    public string $statusFilter = '';

    /** @var array<string, string> */
    public array $statusLabels = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->isPlatformAdmin(), 403);

        $this->statusLabels = SubscriptionStatus::labels();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Title('Assinaturas')]
    public function render()
    {
        $subscriptions = Subscription::query()
            ->with(['plan', 'teacher.user'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->whereHas('teacher', function ($qq) {
                        $qq->where('name', 'LIKE', "%{$this->search}%")
                            ->orWhereHas('user', fn ($u) => $u->where('email', 'LIKE', "%{$this->search}%"));
                    });
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderBy('status')
            ->orderByDesc('updated_at')
            ->paginate(15);

        return view('livewire.dashboard.subscriptions.index', [
            'subscriptions' => $subscriptions,
        ]);
    }
}
