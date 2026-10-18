<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                <x-icon name="bell" class="h-6 w-6 text-brand-600" />
                Notificações Enviadas
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Tudo o que chegou no aplicativo dos seus alunos
                <span class="text-gray-400">(mensagens, planos publicados e lembretes automáticos)</span>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a wire:navigate href="{{ route('students.index') }}" class="btn btn-secondary">
                <x-icon name="users" class="h-4 w-4" />
                Alunos
            </a>
        </div>
    </div>

    {{-- Indicadores --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="card">
            <div class="card-body flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-100 text-brand-700">
                    <x-icon name="bell" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Avisos no recorte</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $counts['total'] }}</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                    <x-icon name="clock" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Aguardando leitura</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $counts['unread'] }}</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                    <x-icon name="check-badge" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Já lidas</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $counts['read'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Resumo por tipo --}}
    @if (count($typeCounts) > 0)
        <div class="card mb-6">
            <div class="card-body flex flex-wrap items-center gap-2">
                <span class="text-sm font-medium text-gray-500">Por tipo:</span>
                @foreach ($typeCounts as $row)
                    <button type="button" wire:click="$set('filterType', '{{ $row['type'] }}')"
                        class="badge badge-secondary hover:bg-brand-100 hover:text-brand-700">
                        {{ $row['label'] }} · {{ $row['count'] }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Filtros --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group mb-md-0">
                        <label for="search">Buscar</label>
                        <input type="text" id="search" class="form-control" placeholder="Título ou texto do aviso"
                            wire:model.live.debounce.400ms="search">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-md-0">
                        <label for="filter_student">Aluno</label>
                        <select id="filter_student" class="form-control" wire:model.live="filterStudentId">
                            <option value="">Todos os alunos</option>
                            @foreach ($studentOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-md-0">
                        <label for="filter_type">Tipo</label>
                        <select id="filter_type" class="form-control" wire:model.live="filterType">
                            <option value="">Todos os tipos</option>
                            @foreach ($types as $class => $label)
                                <option value="{{ $class }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-md-0">
                        <label for="filter_status">Situação</label>
                        <select id="filter_status" class="form-control" wire:model.live="filterStatus">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-3">
                    <div class="form-group mb-md-0">
                        <label for="from">Enviado de</label>
                        <input type="date" id="from" class="form-control" wire:model.live="from">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-md-0">
                        <label for="to">até</label>
                        <input type="date" id="to" class="form-control" wire:model.live="to">
                    </div>
                </div>
                <div class="col-md-6 flex items-end justify-md-end">
                    <button type="button" class="btn btn-secondary btn-sm" wire:click="clearFilters">
                        <x-icon name="x-mark" class="h-4 w-4" /> Limpar filtros
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Lista --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title flex items-center gap-2">
                <x-icon name="queue-list" class="h-5 w-5 text-brand-600" />
                Histórico de envios
            </h3>
            <span class="text-sm text-gray-500">{{ $notifications->total() }} aviso(s)</span>
        </div>

        <div class="card-body table-responsive">
            @if ($notifications->count() > 0)
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Enviado em</th>
                            <th>Aluno</th>
                            <th>Tipo</th>
                            <th>Aviso</th>
                            <th class="text-center">Leitura</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($notifications as $notification)
                            <tr wire:key="notification-{{ $notification->id }}">
                                <td class="whitespace-nowrap text-gray-500">
                                    {{ $notification->created_at?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td>
                                    @if ($notification->student)
                                        <a wire:navigate
                                            href="{{ route('students.show', $notification->student->id) }}"
                                            class="font-medium text-brand-600 hover:text-brand-700">
                                            {{ $notification->student->name }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">Conta removida</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-secondary">
                                        {{ \App\Services\StudentNotificationService::TYPES[$notification->type] ?? 'Aviso' }}
                                    </span>
                                </td>
                                <td>
                                    <p class="mb-0 font-medium text-gray-900">
                                        {{ $notification->data['title'] ?? '—' }}
                                    </p>
                                    <p class="mb-0 text-sm text-gray-500">
                                        {{ $notification->data['message'] ?? '' }}
                                    </p>
                                </td>
                                <td class="text-center">
                                    @if ($notification->read_at)
                                        <span class="badge badge-success">Lida</span>
                                        <p class="mb-0 text-xs text-gray-500">
                                            {{ $notification->read_at->format('d/m/Y H:i') }}
                                        </p>
                                    @else
                                        <span class="badge badge-warning">Não lida</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="card-footer">
                    {{ $notifications->links() }}
                </div>
            @else
                <div class="py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="bell" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-gray-900">
                        @if ($filters['search'] !== '' || $filters['student_id'] || $filters['type'] || $filters['status'] !== '' || $filters['from'] || $filters['to'])
                            Nenhum aviso encontrado com os filtros atuais.
                        @else
                            Nenhum aviso enviado ainda.
                        @endif
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        As mensagens que você envia, os planos publicados e os lembretes
                        automáticos aparecem aqui conforme são entregues.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
