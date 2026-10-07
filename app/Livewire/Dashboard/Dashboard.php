<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardService;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Painel do SaaS: visão geral do professor (ou do admin, com dados globais).
 * Substitui o dashboard legado de blog (imports inexistentes removidos).
 */
class Dashboard extends Component
{
    #[Title('Painel de Controle')]
    public function render(DashboardService $dashboard)
    {
        // Admin vê globais (global scope sem filtro); professor vê só o próprio tenant.
        return view('livewire.dashboard.dashboard', [
            'stats' => $dashboard->teacherDashboard(),
        ]);
    }
}
