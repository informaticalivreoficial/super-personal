<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('sports.index') }}" title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="fire" class="h-6 w-6 text-brand-600" />
                    {{ $isEdit ? 'Editar' : 'Cadastrar' }} modalidade
                </h1>
                <p class="mt-1 text-sm text-gray-500">Modalidades / {{ $isEdit ? 'Editar' : 'Nova' }}</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" autocomplete="off">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="sport_name">Nome *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="sport_name" wire:model="name" placeholder="Ex.: Triathlon">
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="sport_icon">Ícone</label>
                            <input type="text" class="form-control @error('icon') is-invalid @enderror"
                                id="sport_icon" wire:model="icon" placeholder="Ex.: swim (opcional)">
                            @error('icon')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="sport_active">Status</label>
                            <div class="mt-2 flex items-center gap-2">
                                <input type="checkbox" id="sport_active" wire:model="active" class="h-4 w-4 rounded border-gray-300 text-brand-600">
                                <label for="sport_active" class="text-sm text-gray-700">Ativa (disponível para sessões)</label>
                            </div>
                            @error('active')
                                <span class="text-sm text-red-500">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group mb-0">
                            <label for="sport_description">Descrição</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                id="sport_description" rows="3" wire:model="description"
                                placeholder="Descreva a modalidade (opcional)"></textarea>
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                        <x-icon name="check" class="h-4 w-4" /> {{ $isEdit ? 'Salvar alterações' : 'Cadastrar modalidade' }}
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
