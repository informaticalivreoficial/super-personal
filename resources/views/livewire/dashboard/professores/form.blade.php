<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('professors.index') }}" title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="user-plus" class="h-6 w-6 text-teal-600" />
                    {{ $isEdit ? 'Editar' : 'Cadastrar' }} professor
                </h1>
                <p class="mt-1 text-sm text-gray-500">Plataforma / {{ $isEdit ? 'Editar' : 'Novo' }} professor</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" autocomplete="off">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="teacher_name">Nome *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="teacher_name" wire:model="name" placeholder="Nome completo">
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="teacher_email">E-mail *</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                id="teacher_email" wire:model="email" placeholder="professor@exemplo.com">
                            @error('email')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="teacher_phone">Telefone</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                id="teacher_phone" wire:model="phone" placeholder="(00) 00000-0000 (opcional)">
                            @error('phone')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="teacher_specialty">Especialidade</label>
                            <input type="text" class="form-control @error('specialty') is-invalid @enderror"
                                id="teacher_specialty" wire:model="specialty"
                                placeholder="Ex.: Triathlon, Corrida... (opcional)">
                            @error('specialty')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="teacher_password">{{ $isEdit ? 'Nova senha' : 'Senha *' }}</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                id="teacher_password" wire:model="password"
                                placeholder="{{ $isEdit ? 'Deixe em branco para manter a atual' : 'Mínimo de 8 caracteres' }}">
                            @error('password')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="teacher_password_confirmation">Confirmar senha {{ $isEdit ? '' : '*' }}</label>
                            <input type="password" class="form-control"
                                id="teacher_password_confirmation" wire:model="password_confirmation"
                                placeholder="Repita a senha">
                        </div>
                    </div>
                </div>

                @if (! $isEdit)
                    <p class="mt-2 text-xs text-gray-500">
                        O professor receberá acesso ao painel e poderá cadastrar alunos, planos e pagamentos
                        dentro do próprio tenant.
                    </p>
                @endif
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                        <x-icon name="check" class="h-4 w-4" /> {{ $isEdit ? 'Salvar alterações' : 'Cadastrar professor' }}
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
