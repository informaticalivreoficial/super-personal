<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('students.index') }}" title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="academic-cap" class="h-6 w-6 text-brand-600" />
                    {{ $isEdit ? 'Editar aluno' : 'Cadastrar aluno' }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">Alunos / {{ $isEdit ? 'Editar' : 'Novo' }}</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title flex items-center gap-2">
                <x-icon name="identification" class="h-5 w-5 text-brand-600" />
                Dados do aluno
            </h3>
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

                <hr class="my-6 border-gray-100">

                <h5 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-gray-500">
                    <x-icon name="scale" class="h-4 w-4 text-brand-600" />
                    Composição física
                </h5>
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

                <hr class="my-6 border-gray-100">

                <h5 class="mb-3 flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-gray-500">
                    <x-icon name="calendar" class="h-4 w-4 text-brand-600" />
                    Disponibilidade semanal
                </h5>
                <div class="form-group">
                    <div class="flex flex-wrap gap-x-6 gap-y-3">
                        @foreach ([1 => 'Domingo', 2 => 'Segunda', 3 => 'Terça', 4 => 'Quarta', 5 => 'Quinta', 6 => 'Sexta', 7 => 'Sábado'] as $day => $label)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                    wire:model="available_days" value="{{ $day }}" id="day{{ $day }}">
                                <label class="form-check-label" for="day{{ $day }}">{{ $label }}</label>
                            </div>
                        @endforeach
                    </div>
                    @error('available_days')
                        <span class="invalid-feedback">{{ $message }}</span>
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

                <div class="flex items-center gap-3 rounded-lg bg-gray-50 px-4 py-3">
                    <x-forms.switch-toggle wire:model.live="active" :checked="$active" color="blue" />
                    <label class="mb-0 cursor-pointer text-sm font-medium text-gray-700" for="active">
                        Aluno ativo
                    </label>
                </div>
            </div>

            <div class="card-footer flex flex-wrap gap-2">
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                        <x-icon name="check" class="h-4 w-4" />
                        {{ $isEdit ? 'Salvar alterações' : 'Cadastrar aluno' }}
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
