<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('admin') }}" title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="user" class="h-6 w-6 text-brand-600" />
                    Meu perfil
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Conta / Dados pessoais
                </p>
            </div>
        </div>
    </div>

    <form wire:submit="save" autocomplete="off" class="space-y-6">

        {{-- Dados pessoais --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-base font-semibold text-gray-900">Dados pessoais</h2>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="profile_name">Nome</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="profile_name" wire:model="name" placeholder="Seu nome completo">
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="profile_email">E-mail</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                id="profile_email" wire:model="email" placeholder="voce@exemplo.com">
                            @error('email')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Usado para entrar no painel.</p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="profile_phone">Telefone</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                id="profile_phone" wire:model="phone" placeholder="Ex.: 11 99999-8888">
                            @error('phone')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            @if ($isTeacher)
                                <p class="mt-1 text-xs text-gray-500">
                                    Aparece no cadastro do treinador no painel da plataforma.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Alterar senha (opcional) --}}
        <div class="card">
            <div class="card-header">
                <h2 class="text-base font-semibold text-gray-900">Alterar senha</h2>
            </div>

            <div class="card-body">
                <p class="mb-4 text-xs text-gray-500">
                    Deixe os dois campos em branco para manter a senha atual.
                </p>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="profile_password">Nova senha</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                id="profile_password" wire:model="password" autocomplete="new-password"
                                placeholder="Mínimo de 8 caracteres">
                            @error('password')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="profile_password_confirmation">Confirmar nova senha</label>
                            <input type="password" class="form-control"
                                id="profile_password_confirmation" wire:model="password_confirmation"
                                autocomplete="new-password" placeholder="Repita a nova senha">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end">
            <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                    <x-icon name="check" class="h-4 w-4" /> Salvar alterações
                </span>
                <span wire:loading wire:target="save">
                    <x-icon name="arrow-path" class="h-4 w-4 animate-spin" />
                </span>
            </button>
        </div>
    </form>
</div>
