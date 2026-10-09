<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('exercises.index') }}" title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="queue-list" class="h-6 w-6 text-brand-600" />
                    {{ $isEdit ? 'Editar' : 'Cadastrar' }} exercício
                </h1>
                <p class="mt-1 text-sm text-gray-500">Exercícios / {{ $isEdit ? 'Editar' : 'Novo' }}</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" autocomplete="off">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="exercise_sport">Modalidade *</label>
                            <select class="form-control @error('sport_id') is-invalid @enderror"
                                id="exercise_sport" wire:model="sport_id">
                                <option value="">Selecione...</option>
                                @foreach ($sports as $sport)
                                    <option value="{{ $sport->id }}">{{ $sport->name }}</option>
                                @endforeach
                            </select>
                            @error('sport_id')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label for="exercise_name">Nome *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="exercise_name" wire:model="name" placeholder="Ex.: Pedalada em Z2">
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="exercise_difficulty">Dificuldade</label>
                            <select class="form-control @error('difficulty') is-invalid @enderror"
                                id="exercise_difficulty" wire:model="difficulty">
                                <option value="">Não informar</option>
                                @foreach ($difficultyLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('difficulty')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="exercise_description">Descrição</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                id="exercise_description" rows="3" wire:model="description"
                                placeholder="O que é este exercício?"></textarea>
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="exercise_instructions">Instruções</label>
                            <textarea class="form-control @error('instructions') is-invalid @enderror"
                                id="exercise_instructions" rows="3" wire:model="instructions"
                                placeholder="Como executar / orientações de execução"></textarea>
                            @error('instructions')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="exercise_video">Vídeo (URL)</label>
                            <input type="text" class="form-control @error('video_url') is-invalid @enderror"
                                id="exercise_video" wire:model="video_url" placeholder="https://...">
                            @error('video_url')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Status</label>
                            <div class="mt-2 flex items-center gap-2">
                                <input type="checkbox" id="exercise_active" wire:model="active"
                                    class="h-4 w-4 rounded border-gray-300 text-brand-600">
                                <label for="exercise_active" class="text-sm text-gray-700">Ativo (disponível para composição de treinos)</label>
                            </div>
                            @error('active')
                                <span class="text-sm text-red-500">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                        <x-icon name="check" class="h-4 w-4" /> {{ $isEdit ? 'Salvar alterações' : 'Cadastrar exercício' }}
                    </span>
                    <span wire:loading wire:target="save">
                        <x-icon name="arrow-path" class="h-4 w-4 animate-spin" />
                    </span>
                </button>
                <button type="button" class="btn btn-secondary btn-sm" wire:click="cancel">Cancelar</button>
            </div>
        </div>
    </form>
</div>
