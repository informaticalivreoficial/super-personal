<div>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-clipboard-list mr-2"></i>Planos de Treino</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a wire:navigate href="{{ route('admin') }}">Painel de Controle</a></li>
                        <li class="breadcrumb-item active">Planos</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-teal card-outline">
        <div class="card-header">
            <div class="row w-100">
                <div class="col-12 col-sm-6 my-2 order-2 order-sm-1">
                    <div class="card-tools" style="width: 100%; max-width: 280px;">
                        <div class="input-group input-group-sm">
                            <input type="text" wire:model.live.debounce.400ms="search" class="form-control"
                                placeholder="Buscar por plano, meta ou aluno...">
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
                <div class="col-12 col-sm-6 my-2 text-right order-1 order-sm-2">
                    <a wire:navigate href="{{ route('plans.create') }}" class="btn btn-sm btn-teal">
                        <i class="fas fa-plus mr-1"></i> Novo plano
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body table-responsive p-0">
            @if ($plans->count() > 0)
                <table class="table table-hover table-striped text-nowrap">
                    <thead>
                        <tr>
                            <th>Plano</th>
                            <th>Aluno</th>
                            <th>Período</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Semanas</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($plans as $plan)
                            <tr wire:key="plan-{{ $plan->id }}">
                                <td>
                                    <a wire:navigate href="{{ route('plans.show', $plan) }}" class="font-weight-bold">
                                        {{ $plan->name }}
                                    </a>
                                    @if ($plan->goal)
                                        <br><small class="text-muted">{{ $plan->goal }}</small>
                                    @endif
                                </td>
                                <td>{{ $plan->student?->name ?? '—' }}</td>
                                <td>
                                    {{ $plan->start_date?->format('d/m/Y') ?? '—' }}
                                    @if ($plan->end_date)
                                        → {{ $plan->end_date->format('d/m/Y') }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php
                                        $badgeClass = match ($plan->status->value) {
                                            'active' => 'badge-success',
                                            'draft' => 'badge-secondary',
                                            'completed' => 'badge-info',
                                            default => 'badge-danger',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ \App\Enums\TrainingPlanStatus::labels()[$plan->status->value] }}</span>
                                </td>
                                <td class="text-center">{{ $plan->weeks()->count() }}</td>
                                <td class="text-center">
                                    <a wire:navigate href="{{ route('plans.show', $plan) }}"
                                        class="btn btn-xs btn-info text-white" title="Visualizar">
                                        <i class="fas fa-search"></i>
                                    </a>
                                    <a wire:navigate href="{{ route('plans.edit', $plan) }}"
                                        class="btn btn-xs btn-default" title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <button type="button" class="btn btn-xs btn-danger text-white" title="Excluir"
                                        wire:click="confirmDelete({{ $plan->id }})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="card-footer">
                    {{ $plans->links() }}
                </div>
            @else
                <div class="p-3">
                    <div class="alert alert-info mb-0">
                        @if ($search)
                            Nenhum plano encontrado para "<strong>{{ $search }}</strong>".
                        @else
                            Nenhum plano de treino cadastrado ainda.
                            <a wire:navigate href="{{ route('plans.create') }}">Crie o primeiro plano</a>.
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
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger mr-2"></i>Excluir plano</h5>
                        <button type="button" class="close" wire:click="$set('deleteId', null)" aria-label="Fechar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">
                            Tem certeza que deseja excluir este plano? Semanas, sessões e execuções
                            vinculadas serão excluídas em cascata.
                        </p>
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
