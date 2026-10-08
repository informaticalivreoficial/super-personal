<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                <x-icon name="fire" class="h-6 w-6 text-teal-600" />
                Modalidades
            </h1>
            <p class="mt-1 text-sm text-gray-500">Catálogo de modalidades esportivas (corrida, natação, triathlon...)</p>
        </div>
        @if ($canManage)
            <a wire:navigate href="{{ route('sports.create') }}" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Nova modalidade
            </a>
        @endif
    </div>

    <div class="card">
        <div class="card-header">
            <div class="w-full max-w-xs">
                <div class="input-group input-group-sm">
                    <input type="text" wire:model.live.debounce.400ms="search"
                        class="form-control" placeholder="Buscar modalidade...">
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
            @if ($sports->count() > 0)
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Modalidade</th>
                            <th>Slug</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Exercícios</th>
                            @if ($canManage)
                                <th class="text-center">Ações</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sports as $sport)
                            <tr wire:key="sport-{{ $sport->id }}">
                                <td>
                                    <span class="font-semibold text-gray-900">{{ $sport->name }}</span>
                                    @if ($sport->description)
                                        <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($sport->description, 90) }}</small>
                                    @endif
                                </td>
                                <td><code class="text-xs">{{ $sport->slug }}</code></td>
                                <td class="text-center">
                                    <span class="badge {{ $sport->active ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $sport->active ? 'Ativa' : 'Inativa' }}
                                    </span>
                                </td>
                                <td class="text-center">{{ $sport->exercises()->count() }}</td>
                                @if ($canManage)
                                    <td>
                                        <div class="flex items-center justify-center gap-1">
                                            <a wire:navigate href="{{ route('sports.edit', $sport) }}"
                                                class="btn btn-xs btn-secondary" title="Editar">
                                                <x-icon name="pencil" class="h-4 w-4" />
                                            </a>
                                            <button type="button" class="btn btn-xs btn-danger" title="Excluir"
                                                wire:click="confirmDelete({{ $sport->id }})">
                                                <x-icon name="trash" class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="card-footer">
                    {{ $sports->links() }}
                </div>
            @else
                <div class="py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="fire" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-gray-900">
                        @if ($search)
                            Nenhuma modalidade encontrada para "<strong>{{ $search }}</strong>".
                        @else
                            Nenhuma modalidade cadastrada ainda.
                        @endif
                    </p>
                    @if ($canManage && ! $search)
                        <a wire:navigate href="{{ route('sports.create') }}"
                            class="mt-2 inline-block text-sm font-semibold text-teal-600 hover:text-teal-700">
                            Cadastre a primeira modalidade
                        </a>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @include('components.confirm-delete', [
        'confirmTitle' => 'Excluir modalidade',
        'confirmMessage' => 'Tem certeza que deseja excluir esta modalidade? Ela não pode ter exercícios ou sessões vinculados.',
    ])
</div>
