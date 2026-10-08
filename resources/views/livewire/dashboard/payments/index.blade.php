<div>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-dollar-sign mr-2"></i>Pagamentos</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a wire:navigate href="{{ route('admin') }}">Painel de Controle</a></li>
                        <li class="breadcrumb-item active">Pagamentos</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-teal card-outline">
        <div class="card-header">
            <div class="row w-100">
                <div class="col-12 col-md-5 my-2 order-2 order-md-1">
                    <div class="card-tools" style="width: 100%; max-width: 280px;">
                        <div class="input-group input-group-sm">
                            <input type="text" wire:model.live.debounce.400ms="search" class="form-control"
                                placeholder="Buscar por descrição ou aluno...">
                            @if ($search)
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-default" wire:click="$set('search', '')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 my-2 order-3 order-md-2">
                    <select class="form-control form-control-sm" wire:model.live="filterStatus">
                        <option value="">Todos os status</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3 my-2 text-right order-1 order-md-3">
                    <a wire:navigate href="{{ route('payments.create') }}" class="btn btn-sm btn-teal">
                        <i class="fas fa-plus mr-1"></i> Novo pagamento
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body table-responsive p-0">
            @if ($payments->count() > 0)
                <table class="table table-hover table-striped text-nowrap">
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
                                <td class="text-center">
                                    @if ($payment->status->value !== 'paid')
                                        <button type="button" class="btn btn-xs btn-success text-white"
                                            wire:click="markAsPaid({{ $payment->id }})" title="Marcar como pago">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    @endif
                                    <a wire:navigate href="{{ route('payments.edit', $payment) }}"
                                        class="btn btn-xs btn-default" title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <button type="button" class="btn btn-xs btn-danger text-white" title="Excluir"
                                        wire:click="confirmDelete({{ $payment->id }})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="card-footer">
                    {{ $payments->links() }}
                </div>
            @else
                <div class="p-3">
                    <div class="alert alert-info mb-0">
                        @if ($search || $filterStatus)
                            Nenhum pagamento encontrado.
                        @else
                            Nenhum pagamento cadastrado ainda.
                            <a wire:navigate href="{{ route('payments.create') }}">Registre o primeiro</a>.
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if ($deleteId)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger mr-2"></i>Excluir pagamento</h5>
                        <button type="button" class="close" wire:click="$set('deleteId', null)" aria-label="Fechar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Tem certeza que deseja excluir este registro de pagamento?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" wire:click="$set('deleteId', null)">Cancelar</button>
                        <button type="button" class="btn btn-danger" wire:click="delete" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="delete">Excluir</span>
                            <span wire:loading wire:target="delete"><i class="fas fa-spinner fa-spin"></i></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
