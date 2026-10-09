<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                <x-icon name="currency-dollar" class="h-6 w-6 text-brand-600" />
                Pagamentos
            </h1>
            <p class="mt-1 text-sm text-gray-500">Cobranças e recebimentos dos alunos</p>
        </div>
        <a wire:navigate href="{{ route('payments.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Novo pagamento
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="flex w-full flex-wrap items-center gap-2">
                <div class="min-w-[220px] flex-1 max-w-xs">
                    <div class="input-group input-group-sm">
                        <input type="text" wire:model.live.debounce.400ms="search"
                            class="form-control" placeholder="Buscar por descrição ou aluno...">
                        @if ($search)
                            <div class="input-group-append">
                                <button type="button" class="btn btn-default btn-sm"
                                    wire:click="$set('search', '')" title="Limpar busca">
                                    <x-icon name="x-mark" class="h-4 w-4" />
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
                <select class="form-control form-control-sm max-w-[200px]" wire:model.live="filterStatus">
                    <option value="">Todos os status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="card-body table-responsive">
            @if ($payments->count() > 0)
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Descrição</th>
                            <th>Vencimento</th>
                            <th class="text-right">Valor</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Método</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr wire:key="payment-{{ $payment->id }}">
                                <td>{{ $payment->student?->name ?? '—' }}</td>
                                <td>{{ $payment->description }}</td>
                                <td>{{ $payment->due_date?->format('d/m/Y') ?? '—' }}</td>
                                <td class="text-right">R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}</td>
                                <td class="text-center">
                                    @php
                                        $badgeClass = match ($payment->status->value) {
                                            'paid' => 'badge-success',
                                            'overdue' => 'badge-danger',
                                            'cancelled' => 'badge-secondary',
                                            default => 'badge-warning',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ $statuses[$payment->status->value] }}</span>
                                </td>
                                <td class="text-center">
                                    {{ \App\Enums\PaymentMethod::labels()[$payment->payment_method?->value] ?? '—' }}
                                </td>
                                <td>
                                    <div class="flex items-center justify-center gap-1">
                                        @if ($payment->status->value !== 'paid')
                                            <button type="button" class="btn btn-xs btn-success"
                                                wire:click="markAsPaid({{ $payment->id }})" title="Marcar como pago">
                                                <x-icon name="check" class="h-4 w-4" />
                                            </button>
                                        @endif
                                        <a wire:navigate href="{{ route('payments.edit', $payment) }}"
                                            class="btn btn-xs btn-secondary" title="Editar">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </a>
                                        <button type="button" class="btn btn-xs btn-danger" title="Excluir"
                                            wire:click="confirmDelete({{ $payment->id }})">
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="card-footer">
                    {{ $payments->links() }}
                </div>
            @else
                <div class="py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="currency-dollar" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-gray-900">
                        @if ($search || $filterStatus)
                            Nenhum pagamento encontrado.
                        @else
                            Nenhum pagamento cadastrado ainda.
                        @endif
                    </p>
                    @unless ($search || $filterStatus)
                        <a wire:navigate href="{{ route('payments.create') }}"
                            class="mt-2 inline-block text-sm font-semibold text-brand-600 hover:text-brand-700">
                            Registre o primeiro
                        </a>
                    @endunless
                </div>
            @endif
        </div>
    </div>

    @include('components.confirm-delete', [
        'confirmTitle' => 'Excluir pagamento',
        'confirmMessage' => 'Tem certeza que deseja excluir este registro de pagamento?',
    ])
</div>
