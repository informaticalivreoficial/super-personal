<div>
    {{-- Indicadores --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card">
            <div class="card-body">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Saldo disponível</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">
                    R$ {{ number_format($balance, 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Pronto para saque</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">A liberar</p>
                <p class="mt-1 text-2xl font-semibold text-amber-600">
                    R$ {{ number_format($releasingTotal, 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500">
                    @if ($holdDays > 0)
                        Liberado {{ $holdDays }} {{ $holdDays === 1 ? 'dia' : 'dias' }} após o pagamento
                    @else
                        Liberação imediata
                    @endif
                </p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Saque aguardando</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">
                    R$ {{ number_format($pendingTotal, 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Aguardando confirmação</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Já recebido</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">
                    R$ {{ number_format($paidTotal, 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Total transferido a você</p>
            </div>
        </div>
    </div>

    {{-- Saque aguardando confirmação --}}
    @if ($pending)
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
            <p class="flex items-center gap-2 text-sm font-semibold text-amber-800">
                <x-icon name="clock" class="h-5 w-5" />
                Saque aguardando confirmação
            </p>
            <p class="mt-1 text-sm text-amber-700">
                R$ {{ number_format((float) $pending->net_amount, 2, ',', '.') }} solicitado em
                {{ $pending->created_at?->format('d/m/Y H:i') }}
                @if ($pending->destination)
                    para {{ $pending->destination }}
                @endif
                . Enquanto ele não for concluído, não é possível solicitar outro.
            </p>
        </div>
    @endif

    {{-- Cobranças liberadas para saque --}}
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title flex items-center gap-2">
                <x-icon name="banknotes" class="h-5 w-5 text-brand-600" />
                Cobranças liberadas para saque
            </h3>
        </div>

        <div class="card-body">
            @if ($available->isNotEmpty())
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
                            @foreach ($available as $payment)
                                <tr wire:key="available-{{ $payment->id }}">
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
                                <td colspan="5" class="text-right font-medium">Total disponível</td>
                                <td class="text-right font-semibold">
                                    R$ {{ number_format($balance, 2, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if (! $pending)
                    @if ($accounts->isNotEmpty())
                        <div class="mt-4 flex flex-wrap items-end gap-3">
                            <div class="form-group mb-0 min-w-[240px]">
                                <label for="payout-account">Receber em</label>
                                <select id="payout-account" class="form-control" wire:model="accountId">
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}">
                                            {{ $account->displayName() }}{{ $account->is_default ? ' (padrão)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group mb-0 min-w-[220px] flex-1">
                                <label for="payout-notes">Observação (opcional)</label>
                                <input type="text" id="payout-notes" class="form-control"
                                    wire:model="notes" placeholder="Ex.: observação para a plataforma">
                                @error('notes')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            <button type="button" class="btn btn-primary" wire:click="requestPayout"
                                wire:loading.attr="disabled" wire:target="requestPayout">
                                <span wire:loading.remove wire:target="requestPayout" class="flex items-center gap-2">
                                    <x-icon name="banknotes" class="h-4 w-4" />
                                    Solicitar saque
                                </span>
                                <span wire:loading wire:target="requestPayout" class="flex items-center gap-2">
                                    <x-icon name="arrow-path" class="h-4 w-4 animate-spin" />
                                    Solicitando...
                                </span>
                            </button>
                        </div>
                    @else
                        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm font-medium text-amber-800">
                                Cadastre uma chave Pix ou conta bancária para receber.
                            </p>
                            <button type="button" class="btn btn-primary mt-3"
                                wire:click="$dispatch('show-tab', { tab: 'contas' })">
                                <x-icon name="plus" class="h-4 w-4" />
                                Cadastrar forma de recebimento
                            </button>
                        </div>
                    @endif
                @endif
            @else
                <div class="py-8 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="banknotes" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-gray-900">Nenhuma cobrança liberada para saque.</p>
                    <p class="mt-1 text-sm text-gray-500">
                        O saldo aparece aqui quando um aluno paga uma cobrança <strong>online</strong> pelo app
                        @if ($holdDays > 0)
                            e o prazo de liberação ({{ $holdDays }} {{ $holdDays === 1 ? 'dia' : 'dias' }}) passa
                        @endif
                        .
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- A liberar --}}
    @if ($releasing->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h3 class="card-title flex items-center gap-2">
                    <x-icon name="clock" class="h-5 w-5 text-amber-500" />
                    A liberar
                </h3>
            </div>

            <div class="card-body table-responsive">
                <p class="mb-3 text-sm text-gray-500">
                    Valores que já entraram mas ainda cumprem o prazo de segurança
                    @if ($holdDays > 0)
                        ({{ $holdDays }} {{ $holdDays === 1 ? 'dia' : 'dias' }} após o pagamento)
                    @endif
                    .
                </p>
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Pago em</th>
                            <th>Aluno</th>
                            <th>Descrição</th>
                            <th class="text-right">Líquido</th>
                            <th class="text-right">Libera em</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($releasing as $payment)
                            <tr wire:key="releasing-{{ $payment->id }}">
                                <td>{{ $payment->paid_at?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $payment->student?->name ?? '—' }}</td>
                                <td>{{ $payment->description }}</td>
                                <td class="text-right font-semibold">
                                    R$ {{ number_format((float) $payment->net_amount, 2, ',', '.') }}
                                </td>
                                <td class="text-right text-gray-500">
                                    {{ $payment->paid_at?->copy()->addDays($holdDays)->format('d/m/Y') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
