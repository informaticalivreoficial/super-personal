<div>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-user-graduate mr-2"></i>Alunos</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a wire:navigate href="{{ route('admin') }}">Painel de Controle</a></li>
                        <li class="breadcrumb-item active">Alunos</li>
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
                                placeholder="Buscar por nome, e-mail ou telefone...">
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
                    <a wire:navigate href="{{ route('students.create') }}" class="btn btn-sm btn-teal">
                        <i class="fas fa-plus mr-1"></i> Cadastrar aluno
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body table-responsive p-0">
            @if ($students->count() > 0)
                <table class="table table-hover table-striped text-nowrap">
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
                            <tr wire:key="student-{{ $student->id }}" style="{{ $student->active ? '' : 'background: #fffed8 !important;' }}">
                                <td>
                                    <a wire:navigate href="{{ route('students.show', $student) }}" class="font-weight-bold">
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
                                    <button type="button" class="btn btn-xs btn-flat"
                                        wire:click="toggleActive({{ $student->id }})"
                                        title="Clique para {{ $student->active ? 'desativar' : 'ativar' }}">
                                        @if ($student->active)
                                            <span class="badge badge-success">Ativo</span>
                                        @else
                                            <span class="badge badge-secondary">Inativo</span>
                                        @endif
                                    </button>
                                </td>
                                <td class="text-center">
                                    <a wire:navigate href="{{ route('students.show', $student) }}"
                                        class="btn btn-xs btn-info text-white" title="Visualizar">
                                        <i class="fas fa-search"></i>
                                    </a>
                                    <a wire:navigate href="{{ route('students.edit', $student) }}"
                                        class="btn btn-xs btn-default" title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <button type="button" class="btn btn-xs btn-danger text-white" title="Excluir"
                                        wire:click="confirmDelete({{ $student->id }})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="card-footer">
                    {{ $students->links() }}
                </div>
            @else
                <div class="p-3">
                    <div class="alert alert-info mb-0">
                        @if ($search)
                            Nenhum aluno encontrado para "<strong>{{ $search }}</strong>".
                        @else
                            Nenhum aluno cadastrado ainda.
                            <a wire:navigate href="{{ route('students.create') }}">Cadastre o primeiro aluno</a>.
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal de confirmação de exclusão --}}
    @if ($deleteId)
        <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-exclamation-triangle text-danger mr-2"></i>Excluir aluno</h5>
                        <button type="button" class="close" wire:click="$set('deleteId', null)" aria-label="Fechar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">
                            Tem certeza que deseja excluir este aluno? O histórico de treinos e pagamentos
                            será preservado, mas o acesso do aluno será bloqueado.
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
