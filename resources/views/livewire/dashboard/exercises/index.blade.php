<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                <x-icon name="queue-list" class="h-6 w-6 text-brand-600" />
                Exercícios
            </h1>
            <p class="mt-1 text-sm text-gray-500">Biblioteca de exercícios: seus + catálogo global da plataforma</p>
        </div>
        @can('create', \App\Models\Exercise::class)
            <a wire:navigate href="{{ route('exercises.create') }}" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Novo exercício
            </a>
        @endcan
    </div>

    <div class="card">
        <div class="card-header">
            <div class="w-full max-w-xs">
                <div class="input-group input-group-sm">
                    <input type="text" wire:model.live.debounce.400ms="search"
                        class="form-control" placeholder="Buscar exercício...">
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
            @if ($exercises->count() > 0)
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Exercício</th>
                            <th>Modalidade</th>
                            <th class="text-center">Dificuldade</th>
                            <th class="text-center">Origem</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($exercises as $exercise)
                            <tr wire:key="exercise-{{ $exercise->id }}">
                                <td>
                                    <span class="font-semibold text-gray-900">{{ $exercise->name }}</span>
                                    @if ($exercise->description)
                                        <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($exercise->description, 90) }}</small>
                                    @endif
                                </td>
                                <td>{{ $exercise->sport?->name ?? '—' }}</td>
                                <td class="text-center">
                                    @if ($exercise->difficulty)
                                        <span class="badge badge-info">{{ $difficultyLabels[$exercise->difficulty->value] }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($exercise->teacher_id === null)
                                        <span class="badge badge-secondary">Global</span>
                                    @else
                                        <span class="badge badge-success">Meu</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $exercise->active ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $exercise->active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center justify-center gap-1">
                                        @can('update', $exercise)
                                            <a wire:navigate href="{{ route('exercises.edit', $exercise) }}"
                                                class="btn btn-xs btn-secondary" title="Editar">
                                                <x-icon name="pencil" class="h-4 w-4" />
                                            </a>
                                            <button type="button" class="btn btn-xs btn-danger" title="Excluir"
                                                wire:click="confirmDelete({{ $exercise->id }})">
                                                <x-icon name="trash" class="h-4 w-4" />
                                            </button>
                                        @else
                                            <span class="text-xs text-muted" title="Exercício global da plataforma">somente leitura</span>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="card-footer">
                    {{ $exercises->links() }}
                </div>
            @else
                <div class="py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="queue-list" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-gray-900">
                        @if ($search)
                            Nenhum exercício encontrado para "<strong>{{ $search }}</strong>".
                        @else
                            Nenhum exercício cadastrado ainda.
                        @endif
                    </p>
                    @if (! $search)
                        <a wire:navigate href="{{ route('exercises.create') }}"
                            class="mt-2 inline-block text-sm font-semibold text-brand-600 hover:text-brand-700">
                            Cadastre o primeiro exercício
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @include('components.confirm-delete', [
        'confirmTitle' => 'Excluir exercício',
        'confirmMessage' => 'Tem certeza que deseja excluir este exercício? Itens de treino vinculados ficarão sem referência.',
    ])
</div>
