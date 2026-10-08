<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('plans.index') }}" title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="clipboard-document-list" class="h-6 w-6 text-teal-600" />
                    {{ $isEdit ? 'Editar plano' : 'Novo plano de treino' }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">Planos / {{ $isEdit ? 'Editar' : 'Novo' }}</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title flex items-center gap-2">
                <x-icon name="calendar" class="h-5 w-5 text-teal-600" />
                Dados do plano
            </h3>
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

            <div class="card-footer flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                        <x-icon name="check" class="h-4 w-4" />
                        {{ $isEdit ? 'Salvar alterações' : 'Criar plano' }}
                    </span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <x-icon name="arrow-path" class="h-4 w-4 animate-spin" />
                        Salvando...
                    </span>
                </button>
                <button type="button" class="btn btn-secondary" wire:click="cancel">
                    <x-icon name="x-mark" class="h-4 w-4" />
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>
