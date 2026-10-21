<?php

namespace App\Livewire\Dashboard;

use App\Enums\TrainingSessionStatus;
use App\Services\DashboardService;
use App\Services\OnboardingService;
use App\Traits\WithToastr;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Painel do SaaS: visão geral do treinador (tenant) ou do admin
 * (visão da plataforma: tenants, alunos e assinaturas).
 * Substitui o dashboard legado de blog (imports inexistentes removidos).
 */
class Dashboard extends Component
{
    use WithToastr;

    #[Title('Painel de Controle')]
    public function render(DashboardService $dashboard, OnboardingService $onboarding)
    {
        $user = auth()->user();
        $isAdmin = (bool) $user?->isPlatformAdmin();
        $teacher = $isAdmin ? null : $user?->teacher;

        return view('livewire.dashboard.dashboard', [
            'isAdmin' => $isAdmin,
            'stats' => $isAdmin
                ? $dashboard->platformDashboard()
                : $dashboard->teacherDashboard(),
            // Primeiros passos do treinador (some quando concluído/dispensado).
            'onboarding' => $teacher ? $onboarding->progress($teacher) : null,
            // Labels pt-BR dos status usados nas listas do painel do treinador.
            'sessionStatuses' => TrainingSessionStatus::labels(),
        ]);
    }

    public function dismissOnboarding(OnboardingService $onboarding): void
    {
        $teacher = auth()->user()?->teacher;

        abort_unless($teacher !== null, 403);

        $onboarding->dismiss($teacher);

        $this->toastInfo('Checklist dispensado.');
    }
}
