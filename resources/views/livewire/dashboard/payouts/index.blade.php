<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                <x-icon name="banknotes" class="h-6 w-6 text-brand-600" />
                Financeiro
            </h1>
            <p class="mt-1 text-sm text-gray-500">Saldo, extrato e formas de recebimento das cobranças online</p>
        </div>
        <a wire:navigate href="{{ route('payments.index') }}" class="btn btn-secondary">
            <x-icon name="currency-dollar" class="h-4 w-4" />
            Pagamentos
        </a>
    </div>

    {{-- Abas --}}
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex flex-wrap gap-6" aria-label="Seções do Financeiro">
            @foreach ($tabs as $key => $label)
                <button type="button" wire:click="showTab('{{ $key }}')"
                    class="border-b-2 px-1 pb-3 text-sm font-medium transition {{ $tab === $key
                        ? 'border-brand-600 text-brand-700'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    @if ($tab === 'saldo')
        @livewire('dashboard.payouts.payout-balance', key('payout-balance'))
    @elseif ($tab === 'extrato')
        @livewire('dashboard.payouts.payout-statement', key('payout-statement'))
    @else
        @livewire('dashboard.payouts.payout-accounts', key('payout-accounts'))
    @endif
</div>
