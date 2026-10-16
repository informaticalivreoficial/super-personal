<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                <x-icon name="banknotes" class="h-6 w-6 text-brand-600" />
                Repasses
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Líquido das cobranças online dos alunos, a transferir aos treinadores
            </p>
        </div>
        <a wire:navigate href="{{ route('subscriptions.index') }}" class="btn btn-secondary">
            <x-icon name="credit-card" class="h-4 w-4" />
            Assinaturas
        </a>
    </div>

    {{-- KPIs --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="card">
            <div class="card-body">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">A repassar (solicitado)</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">
                    R$ {{ number_format($pendingTotal, 2, ',', '.') }}
                </p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Já repassado</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">
                    R$ {{ number_format($paidTotal, 2, ',', '.') }}
                </p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="flex w-full items-center justify-end">
                <select class="form-control form-control-sm max-w-[200px]" wire:model.live="filterStatus">
                    <option value="">Todos os status</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="card-body table-responsive">
            @if ($payouts->count() > 0)
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Treinador</th>
                            <th>Solicitado em</th>
                            <th class="text-right">Bruto</th>
                            <th class="text-right">Taxas</th>
                            <th class="text-right">Líquido</th>
                            <th class="text-center">Status</th>
                            <th>Pago em</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payouts as $payout)
                            <tr wire:key="payout-{{ $payout->id }}">
                                <td>{{ $payout->teacher?->name ?? '—' }}</td>
                                <td>{{ $payout->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="text-right">R$ {{ number_format((float) $payout->amount, 2, ',', '.') }}</td>
                                <td class="text-right text-gray-500">
                                    - R$ {{ number_format((float) $payout->fee_total, 2, ',', '.') }}
                                </td>
                                <td class="text-right font-semibold">
                                    R$ {{ number_format((float) $payout->net_amount, 2, ',', '.') }}
                                </td>
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
                                <td>
                                    <div class="flex items-center justify-center gap-1">
                                        @if ($payout->status->value === 'requested')
                                            <button type="button" class="btn btn-xs btn-success"
                                                wire:click="markPaid({{ $payout->id }})" title="Marcar como pago">
                                                <x-icon name="check" class="h-4 w-4" />
                                            </button>
                                            <button type="button" class="btn btn-xs btn-danger"
                                                wire:click="cancel({{ $payout->id }})"
                                                wire:confirm="As cobranças deste repasse voltarão ao saldo do treinador. Confirmar?"
                                                title="Cancelar repasse">
                                                <x-icon name="x-mark" class="h-4 w-4" />
                                            </button>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="card-footer">
                    {{ $payouts->links() }}
                </div>
            @else
                <div class="py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="banknotes" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-gray-900">Nenhum repasse solicitado.</p>
                    <p class="mt-1 text-sm text-gray-500">
                        Aparece aqui quando um treinador solicita o repasse das cobranças online.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
