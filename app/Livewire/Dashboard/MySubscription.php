<?php

namespace App\Livewire\Dashboard;

use App\Enums\SubscriptionStatus;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Página "Minha assinatura" — visão somente leitura do treinador sobre a
 * própria assinatura COM a plataforma (billing manual gerido pelo admin
 * no card ProfessorSubscription). Sem escrita aqui: o treinador consulta
 * plano, status e vigência; alterações são exclusivas do admin.
 */
class MySubscription extends Component
{
    public function mount(): void
    {
        // Só o treinador chega aqui (middleware role:teacher); garante perfil.
        abort_unless(auth()->user()?->teacher, 403, 'Perfil de treinador não encontrado.');
    }

    #[Title('Minha assinatura')]
    public function render()
    {
        $teacher = auth()->user()->teacher;

        return view('livewire.dashboard.my-subscription', [
            'subscription' => $teacher->subscription,
            'statusLabels' => SubscriptionStatus::labels(),
            'badgeClasses' => [
                'trial' => 'badge-info',
                'active' => 'badge-success',
                'past_due' => 'badge-warning',
                'cancelled' => 'badge-secondary',
                'expired' => 'badge-danger',
            ],
        ]);
    }
}
