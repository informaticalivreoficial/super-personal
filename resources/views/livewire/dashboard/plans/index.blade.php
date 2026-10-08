<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                <x-icon name="clipboard-document-list" class="h-6 w-6 text-teal-600" />
                Planos de Treino
            </h1>
            <p class="mt-1 text-sm text-gray-500">Planos, semanas e sessões dos seus alunos</p>
        </div>
        <a wire:navigate href="{{ route('plans.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Novo plano
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="w-full max-w-xs">
                <div class="input-group input-group-sm">
                    <input type="text" wire:model.live.debounce.400ms="search"
                        class="form-control" placeholder="Buscar por plano, meta ou aluno...">
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
        </div>

        <div class="card-body table-responsive">
            @if ($plans->count() > 0)
                <table class="table table-hover table-striped">
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
                                    <a wire:navigate href="{{ route('plans.show', $plan) }}"
                                        class="font-semibold text-gray-900 hover:text-teal-600">
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
                                <td>
                                    <div class="flex items-center justify-center gap-1">
                                        <a wire:navigate href="{{ route('plans.show', $plan) }}"
                                            class="btn btn-xs btn-secondary" title="Visualizar">
                                            <x-icon name="eye" class="h-4 w-4" />
                                        </a>
                                        <a wire:navigate href="{{ route('plans.edit', $plan) }}"
                                            class="btn btn-xs btn-secondary" title="Editar">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </a>
                                        <button type="button" class="btn btn-xs btn-danger" title="Excluir"
                                            wire:click="confirmDelete({{ $plan->id }})">
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="card-footer">
                    {{ $plans->links() }}
                </div>
            @else
                <div class="py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="clipboard-document-list" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-gray-900">
                        @if ($search)
                            Nenhum plano encontrado para "<strong>{{ $search }}</strong>".
                        @else
                            Nenhum plano de treino cadastrado ainda.
                        @endif
                    </p>
                    @unless ($search)
                        <a wire:navigate href="{{ route('plans.create') }}"
                            class="mt-2 inline-block text-sm font-semibold text-teal-600 hover:text-teal-700">
                            Crie o primeiro plano
                        </a>
                    @endunless
                </div>
            @endif
        </div>
    </div>

    @include('components.confirm-delete', [
        'confirmTitle' => 'Excluir plano',
        'confirmMessage' => 'Tem certeza que deseja excluir este plano? Semanas, sessões e execuções vinculadas serão excluídas em cascata.',
    ])
</div>
