<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                <x-icon name="users" class="h-6 w-6 text-teal-600" />
                Professores
            </h1>
            <p class="mt-1 text-sm text-gray-500">Professores (tenants) cadastrados na plataforma</p>
        </div>
        <a wire:navigate href="{{ route('professors.create') }}" class="btn btn-primary">
            <x-icon name="user-plus" class="h-4 w-4" />
            Novo professor
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="w-full max-w-xs">
                <div class="input-group input-group-sm">
                    <input type="text" wire:model.live.debounce.400ms="search"
                        class="form-control" placeholder="Buscar por nome ou e-mail...">
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
            @if ($teachers->count() > 0)
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Professor</th>
                            <th>E-mail</th>
                            <th class="text-center">Alunos</th>
                            <th class="text-center">Assinatura</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($teachers as $teacher)
                            <tr wire:key="teacher-{{ $teacher->id }}">
                                <td>
                                    <span class="font-semibold text-gray-900">{{ $teacher->name }}</span>
                                    @if ($teacher->specialty)
                                        <br><small class="text-muted">{{ $teacher->specialty }}</small>
                                    @endif
                                </td>
                                <td>{{ $teacher->user?->email }}</td>
                                <td class="text-center">{{ $teacher->students_count }}</td>
                                <td class="text-center">
                                    @if ($teacher->subscription)
                                        <span class="badge {{ $subscriptionBadgeClasses[$teacher->subscription->status->value] ?? 'badge-secondary' }}">
                                            {{ $statusLabels[$teacher->subscription->status->value] ?? $teacher->subscription->status->value }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">Sem assinatura</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $teacher->active ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $teacher->active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center justify-center gap-1">
                                        <a wire:navigate href="{{ route('professors.edit', $teacher) }}"
                                            class="btn btn-xs btn-secondary" title="Editar">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </a>
                                        <button type="button"
                                            class="btn btn-xs {{ $teacher->active ? 'btn-danger' : 'btn-success' }}"
                                            title="{{ $teacher->active ? 'Desativar' : 'Ativar' }}"
                                            wire:click="toggleActive({{ $teacher->id }})"
                                            wire:confirm="Alterar o status do professor {{ $teacher->name }}?">
                                            <x-icon name="{{ $teacher->active ? 'minus' : 'check' }}"
                                                class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="card-footer">
                    {{ $teachers->links() }}
                </div>
            @else
                <div class="py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="users" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-gray-900">
                        @if ($search)
                            Nenhum professor encontrado para "<strong>{{ $search }}</strong>".
                        @else
                            Nenhum professor cadastrado ainda.
                        @endif
                    </p>
                    @if (! $search)
                        <a wire:navigate href="{{ route('professors.create') }}"
                            class="mt-2 inline-block text-sm font-semibold text-teal-600 hover:text-teal-700">
                            Cadastre o primeiro professor
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
