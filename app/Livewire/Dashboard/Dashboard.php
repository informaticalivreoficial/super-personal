<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Painel do SaaS: visão geral do treinador (tenant) ou do admin
 * (visão da plataforma: tenants, alunos e assinaturas).
 * Substitui o dashboard legado de blog (imports inexistentes removidos).
 */
class Dashboard extends Component
{
    #[Title('Painel de Controle')]
    public function render(DashboardService $dashboard)
    {
        $isAdmin = (bool) auth()->user()?->isPlatformAdmin();

        return view('livewire.dashboard.dashboard', [
            'isAdmin' => $isAdmin,
            'stats' => $isAdmin
                ? $dashboard->platformDashboard()
                : $dashboard->teacherDashboard(),
        ]);
    }
}
