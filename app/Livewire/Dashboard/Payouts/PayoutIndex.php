<?php

namespace App\Livewire\Dashboard\Payouts;

use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Área **Financeiro** do treinador (rota `/admin/repasses`).
 *
 * Concentra o dinheiro das cobranças online: saldo/saque (com o prazo de
 * liberação), extrato das movimentações e as formas de recebimento (chave Pix
 * ou conta bancária). Cada aba é um componente aninhado; esta classe apenas
 * controla a navegação e a autorização (exclusiva do treinador).
 */
#[Layout('components.layouts.app')]
#[Title('Financeiro')]
class PayoutIndex extends Component
{
    /** Abas disponíveis (chave => rótulo). */
    public const TABS = [
        'saldo' => 'Saldo e saque',
        'extrato' => 'Extrato',
        'contas' => 'Formas de recebimento',
    ];

    public string $tab = 'saldo';

    public function mount(): void
    {
        // A rota é role:teacher (o admin não tem tenant/saldo próprio).
        abort_unless(auth()->user()?->teacher, 403, 'Perfil de treinador não encontrado.');
    }

    public function showTab(string $tab): void
    {
        if (array_key_exists($tab, self::TABS)) {
            $this->tab = $tab;
        }
    }

    /** Componentes filhos pedem a troca de aba (ex.: saldo sem conta → contas). */
    #[On('show-tab')]
    public function onShowTab(string $tab = 'saldo'): void
    {
        $this->showTab($tab);
    }

    public function render()
    {
        return view('livewire.dashboard.payouts.index', [
            'tabs' => self::TABS,
        ]);
    }
}
