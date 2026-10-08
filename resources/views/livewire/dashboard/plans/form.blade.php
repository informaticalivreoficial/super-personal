<div>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">
                        <i class="fas fa-clipboard-list mr-2"></i>{{ $isEdit ? 'Editar plano' : 'Novo plano de treino' }}
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a wire:navigate href="{{ route('admin') }}">Painel</a></li>
                        <li class="breadcrumb-item"><a wire:navigate href="{{ route('plans.index') }}">Planos</a></li>
                        <li class="breadcrumb-item active">{{ $isEdit ? 'Editar' : 'Cadastrar' }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-teal card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-alt mr-2"></i>Dados do plano</h3>
                </div>

                <form wire:submit="save">
                    <div class="card-body">
                        <div class="row">
                            @if ($isEdit)
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Aluno</label>
                                        <input type="text" class="form-control" value="{{ $planStudent?->name ?? '—' }}" readonly>
                                        <small class="text-muted">O aluno do plano não pode ser alterado.</small>
                                    </div>
                                </div>
                            @else
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="student_id">Aluno *</label>
                                        <select class="form-control @error('student_id') is-invalid @enderror"
                                            id="student_id" wire:model="student_id">
                                            <option value="">Selecione o aluno...</option>
                                            @foreach ($students as $student)
                                                <option value="{{ $student->id }}">{{ $student->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('student_id')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            @endif

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Nome do plano *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" wire:model="name" placeholder="Ex.: Preparação Maratona 2026">
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="goal">Meta</label>
                                    <input type="text" class="form-control @error('goal') is-invalid @enderror"
                                        id="goal" wire:model="goal" placeholder="Ex.: Correr 42km em menos de 3h30">
                                    @error('goal')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="description">Descrição</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                        id="description" rows="2" wire:model="description"
                                        placeholder="Visão geral do plano..."></textarea>
                                    @error('description')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="start_date">Início *</label>
                                    <input type="date" class="form-control @error('start_date') is-invalid @enderror"
                                        id="start_date" wire:model="start_date">
                                    @error('start_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="end_date">Fim</label>
                                    <input type="date" class="form-control @error('end_date') is-invalid @enderror"
                                        id="end_date" wire:model="end_date">
                                    @error('end_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control @error('status') is-invalid @enderror"
                                        id="status" wire:model="status">
                                        @foreach ($statuses as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('status')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="notes">Observações</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror"
                                        id="notes" rows="3" wire:model="notes"
                                        placeholder="Notas internas sobre o plano..."></textarea>
                                    @error('notes')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-teal" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">
                                <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Salvar alterações' : 'Criar plano' }}
                            </span>
                            <span wire:loading wire:target="save">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Salvando...
                            </span>
                        </button>
                        <button type="button" class="btn btn-default" wire:click="cancel">
                            <i class="fas fa-times mr-1"></i> Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
