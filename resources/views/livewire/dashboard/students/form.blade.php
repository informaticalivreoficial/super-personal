<div>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">
                        <i class="fas fa-user-graduate mr-2"></i>{{ $isEdit ? 'Editar aluno' : 'Cadastrar aluno' }}
                    </h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a wire:navigate href="{{ route('admin') }}">Painel</a></li>
                        <li class="breadcrumb-item"><a wire:navigate href="{{ route('students.index') }}">Alunos</a></li>
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
                    <h3 class="card-title"><i class="fas fa-id-card mr-2"></i>Dados do aluno</h3>
                </div>

                <form wire:submit="save">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Nome completo *</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" wire:model="name" placeholder="Nome do aluno">
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">E-mail *</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                        id="email" wire:model="email" placeholder="email@exemplo.com">
                                    @error('email')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="phone">Telefone / WhatsApp</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                        id="phone" wire:model="phone" placeholder="(00) 00000-0000">
                                    @error('phone')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="birth_date">Data de nascimento</label>
                                    <input type="date" class="form-control @error('birth_date') is-invalid @enderror"
                                        id="birth_date" wire:model="birth_date">
                                    @error('birth_date')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="document">Documento (CPF/RG)</label>
                                    <input type="text" class="form-control @error('document') is-invalid @enderror"
                                        id="document" wire:model="document">
                                    @error('document')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="gender">Sexo</label>
                                    <select class="form-control @error('gender') is-invalid @enderror"
                                        id="gender" wire:model="gender">
                                        <option value="">Selecione...</option>
                                        @foreach ($genders as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('gender')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="started_at">Início dos treinos</label>
                                    <input type="date" class="form-control @error('started_at') is-invalid @enderror"
                                        id="started_at" wire:model="started_at">
                                    @error('started_at')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="fitness_level">Nível de condicionamento</label>
                                    <select class="form-control @error('fitness_level') is-invalid @enderror"
                                        id="fitness_level" wire:model="fitness_level">
                                        <option value="">Selecione...</option>
                                        @foreach ($levels as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('fitness_level')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h5 class="text-muted mb-3"><i class="fas fa-weight mr-2"></i>Composição física</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="height">Altura (cm)</label>
                                    <input type="number" min="50" max="260"
                                        class="form-control @error('height') is-invalid @enderror"
                                        id="height" wire:model="height" placeholder="Ex.: 175">
                                    @error('height')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="initial_weight">Peso inicial (kg)</label>
                                    <input type="number" step="0.1" min="20" max="400"
                                        class="form-control @error('initial_weight') is-invalid @enderror"
                                        id="initial_weight" wire:model="initial_weight" placeholder="Ex.: 80.5">
                                    @error('initial_weight')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="current_weight">Peso atual (kg)</label>
                                    <input type="number" step="0.1" min="20" max="400"
                                        class="form-control @error('current_weight') is-invalid @enderror"
                                        id="current_weight" wire:model="current_weight" placeholder="Ex.: 78.2">
                                    @error('current_weight')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="target_weight">Peso meta (kg)</label>
                                    <input type="number" step="0.1" min="20" max="400"
                                        class="form-control @error('target_weight') is-invalid @enderror"
                                        id="target_weight" wire:model="target_weight" placeholder="Ex.: 74">
                                    @error('target_weight')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="goal">Objetivo</label>
                                    <input type="text" class="form-control @error('goal') is-invalid @enderror"
                                        id="goal" wire:model="goal" placeholder="Ex.: Emagrecer, preparação para maratona...">
                                    @error('goal')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="training_experience">Experiência de treino</label>
                                    <input type="text" class="form-control @error('training_experience') is-invalid @enderror"
                                        id="training_experience" wire:model="training_experience"
                                        placeholder="Ex.: 2 anos correndo, iniciante em natação...">
                                    @error('training_experience')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <hr>

                        <h5 class="text-muted mb-3"><i class="fas fa-calendar-week mr-2"></i>Disponibilidade semanal</h5>
                        <div class="form-group">
                            <div class="d-flex flex-wrap gap-3">
                                @foreach ([1 => 'Domingo', 2 => 'Segunda', 3 => 'Terça', 4 => 'Quarta', 5 => 'Quinta', 6 => 'Sexta', 7 => 'Sábado'] as $day => $label)
                                    <div class="custom-control custom-checkbox mr-3 mb-2">
                                        <input class="custom-control-input" type="checkbox"
                                            wire:model="available_days" value="{{ $day }}"
                                            id="day{{ $day }}">
                                        <label class="custom-control-label" for="day{{ $day }}">{{ $label }}</label>
                                    </div>
                                @endforeach
                            </div>
                            @error('available_days')
                                <span class="text-danger text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="observations">Observações</label>
                            <textarea class="form-control @error('observations') is-invalid @enderror"
                                id="observations" rows="3" wire:model="observations"
                                placeholder="Lesões, restrições, preferências..."></textarea>
                            @error('observations')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="active"
                                wire:model="active">
                            <label class="custom-control-label" for="active">Aluno ativo</label>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-teal" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">
                                <i class="fas fa-save mr-1"></i> {{ $isEdit ? 'Salvar alterações' : 'Cadastrar aluno' }}
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
