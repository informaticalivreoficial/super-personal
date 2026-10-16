<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                <x-icon name="banknotes" class="h-6 w-6 text-brand-600" />
                Repasses
            </h1>
            <p class="mt-1 text-sm text-gray-500">Líquido das cobranças online pagas pelos alunos</p>
        </div>
        <a wire:navigate href="{{ route('payments.index') }}" class="btn btn-secondary">
            <x-icon name="currency-dollar" class="h-4 w-4" />
            Pagamentos
        </a>
    </div>

    {{-- KPIs --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="card">
            <div class="card-body">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Saldo disponível</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">
                    R$ {{ number_format($balance, 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Aguardando solicitação de repasse</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Repasse solicitado</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">
                    R$ {{ number_format($pendingTotal, 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Aguardando confirmação da plataforma</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Já repassado</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">
                    R$ {{ number_format($paidTotal, 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Total transferido a você</p>
            </div>
        </div>
    </div>

    {{-- Aguardando repasse --}}
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title flex items-center gap-2">
                <x-icon name="clock" class="h-5 w-5 text-brand-600" />
                Cobranças aguardando repasse
            </h3>
        </div>

        <div class="card-body">
            @if ($awaiting->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>Pago em</th>
                                <th>Aluno</th>
                                <th>Descrição</th>
                                <th class="text-right">Valor</th>
                                <th class="text-right">Taxa</th>
                                <th class="text-right">Líquido</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($awaiting as $payment)
                                <tr wire:key="awaiting-{{ $payment->id }}">
                                    <td>{{ $payment->paid_at?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $payment->student?->name ?? '—' }}</td>
                                    <td>{{ $payment->description }}</td>
                                    <td class="text-right">R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}</td>
                                    <td class="text-right text-gray-500">
                                        - R$ {{ number_format((float) $payment->fee_amount, 2, ',', '.') }}
                                    </td>
                                    <td class="text-right font-semibold">
                                        R$ {{ number_format((float) $payment->net_amount, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-right font-medium">Total a receber</td>
                                <td class="text-right font-semibold">
                                    R$ {{ number_format($balance, 2, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-4 flex flex-wrap items-end gap-3">
                    <div class="form-group mb-0 min-w-[240px] flex-1">
                        <label for="payout-notes">Observação do pedido (opcional)</label>
                        <input type="text" id="payout-notes" class="form-control"
                            wire:model="notes" placeholder="Ex.: chave Pix para o depósito">
                    </div>
                    <button type="button" class="btn btn-primary" wire:click="requestPayout"
                        wire:loading.attr="disabled" wire:target="requestPayout">
                        <span wire:loading.remove wire:target="requestPayout" class="flex items-center gap-2">
                            <x-icon name="banknotes" class="h-4 w-4" />
                            Solicitar repasse
                        </span>
                        <span wire:loading wire:target="requestPayout" class="flex items-center gap-2">
                            <x-icon name="arrow-path" class="h-4 w-4 animate-spin" />
                            Solicitando...
                        </span>
                    </button>
                </div>
            @else
                <div class="py-8 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="banknotes" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-gray-900">Nenhuma cobrança aguardando repasse.</p>
                    <p class="mt-1 text-sm text-gray-500">
                        O saldo aparece aqui quando um aluno paga uma cobrança <strong>online</strong> pelo app.
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- Histórico --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title flex items-center gap-2">
                <x-icon name="arrow-path" class="h-5 w-5 text-brand-600" />
                Histórico de repasses
            </h3>
        </div>

        <div class="card-body table-responsive">
            @if ($payouts->isNotEmpty())
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Solicitado em</th>
                            <th class="text-right">Bruto</th>
                            <th class="text-right">Taxas</th>
                            <th class="text-right">Líquido</th>
                            <th class="text-center">Status</th>
                            <th>Pago em</th>
                            <th>Observação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payouts as $payout)
                            <tr wire:key="payout-{{ $payout->id }}">
                                <td>{{ $payout->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="text-right">R$ {{ number_format((float) $payout->amount, 2, ',', '.') }}</td>
                                <td class="text-right text-gray-500">
                                    - R$ {{ number_format((float) $payout->fee_total, 2, ',', '.') }}
                                </td>
                                <td class="text-right font-semibold">R$ {{ number_format((float) $payout->net_amount, 2, ',', '.') }}</td>
                                <td class="text-center">
                                    @php
                                        $badge = match ($payout->status->value) {
                                            'paid' => 'badge-success',
                                            'cancelled' => 'badge-secondary',
                                            default => 'badge-warning',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ $statusLabels[$payout->status->value] }}</span>
                                </td>
                                <td>{{ $payout->paid_at?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $payout->notes ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="py-8 text-center">
                    <p class="text-sm font-medium text-gray-900">Nenhum repasse solicitado ainda.</p>
                </div>
            @endif
        </div>
    </div>

    @if ($pixKey)
        <p class="mt-4 text-xs text-gray-500">
            A transferência é feita para a chave Pix cadastrada no seu cadastro.
        </p>
    @else
        <p class="mt-4 text-xs text-gray-500">
            Cadastre sua chave Pix no "Meu perfil" para agilizar o repasse.
        </p>
    @endif
</div>
