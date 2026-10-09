<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                <x-icon name="academic-cap" class="h-6 w-6 text-brand-600" />
                Alunos
            </h1>
            <p class="mt-1 text-sm text-gray-500">Gerencie os alunos da sua equipe</p>
        </div>
        <a wire:navigate href="{{ route('students.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" />
            Cadastrar aluno
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="w-full max-w-xs">
                <div class="input-group input-group-sm">
                    <input type="text" wire:model.live.debounce.400ms="search"
                        class="form-control" placeholder="Buscar por nome, e-mail ou telefone...">
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
            @if ($students->count() > 0)
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Telefone</th>
                            <th class="text-center">Nível</th>
                            <th class="text-center">Peso atual</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($students as $student)
                            <tr wire:key="student-{{ $student->id }}"
                                class="{{ $student->active ? '' : 'bg-amber-50/60' }}">
                                <td>
                                    <a wire:navigate href="{{ route('students.show', $student) }}"
                                        class="font-semibold text-gray-900 hover:text-brand-600">
                                        {{ $student->name }}
                                    </a>
                                </td>
                                <td>{{ $student->email }}</td>
                                <td>{{ $student->phone ?: '—' }}</td>
                                <td class="text-center">
                                    {{ \App\Enums\StudentLevel::labels()[$student->fitness_level?->value] ?? '—' }}
                                </td>
                                <td class="text-center">
                                    {{ $student->current_weight ? number_format((float) $student->current_weight, 1, ',', '.') . ' kg' : '—' }}
                                </td>
                                <td class="text-center">
                                    <button type="button" class="rounded-full focus:outline-none focus:ring-2 focus:ring-brand-400"
                                        wire:click="toggleActive({{ $student->id }})"
                                        title="Clique para {{ $student->active ? 'desativar' : 'ativar' }}">
                                        @if ($student->active)
                                            <span class="badge badge-success">Ativo</span>
                                        @else
                                            <span class="badge badge-secondary">Inativo</span>
                                        @endif
                                    </button>
                                </td>
                                <td>
                                    <div class="flex items-center justify-center gap-1">
                                        <a wire:navigate href="{{ route('students.show', $student) }}"
                                            class="btn btn-xs btn-secondary" title="Visualizar">
                                            <x-icon name="eye" class="h-4 w-4" />
                                        </a>
                                        <a wire:navigate href="{{ route('students.edit', $student) }}"
                                            class="btn btn-xs btn-secondary" title="Editar">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </a>
                                        <button type="button" class="btn btn-xs btn-danger"
                                            title="Excluir"
                                            wire:click="confirmDelete({{ $student->id }})">
                                            <x-icon name="trash" class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="card-footer">
                    {{ $students->links() }}
                </div>
            @else
                <div class="py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="academic-cap" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-gray-900">
                        @if ($search)
                            Nenhum aluno encontrado para "<strong>{{ $search }}</strong>".
                        @else
                            Nenhum aluno cadastrado ainda.
                        @endif
                    </p>
                    @unless ($search)
                        <a wire:navigate href="{{ route('students.create') }}"
                            class="mt-2 inline-block text-sm font-semibold text-brand-600 hover:text-brand-700">
                            Cadastre o primeiro aluno
                        </a>
                    @endunless
                </div>
            @endif
        </div>
    </div>

    @include('components.confirm-delete', [
        'confirmTitle' => 'Excluir aluno',
        'confirmMessage' => 'Tem certeza que deseja excluir este aluno? O histórico de treinos e pagamentos será preservado, mas o acesso do aluno será bloqueado.',
    ])
</div>
