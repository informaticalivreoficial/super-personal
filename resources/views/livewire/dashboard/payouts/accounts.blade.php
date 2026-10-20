<div>
    {{-- Cabeçalho --}}
    <div class="card mb-6">
        <div class="card-header flex items-center justify-between gap-4">
            <div>
                <h3 class="card-title flex items-center gap-2">
                    <x-icon name="credit-card" class="h-5 w-5 text-brand-600" />
                    Formas de recebimento
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Para onde o dinheiro dos saques é transferido. Uma delas é a padrão.
                </p>
            </div>
            <button type="button" class="btn btn-primary" wire:click="openModal">
                <x-icon name="plus" class="h-4 w-4" />
                Nova forma
            </button>
        </div>

        <div class="card-body">
            @if ($accounts->isNotEmpty())
                <div class="space-y-3">
                    @foreach ($accounts as $account)
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 p-4"
                            wire:key="account-{{ $account->id }}">
                            <div class="min-w-0">
                                <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-gray-900">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700">
                                        {{ $accountTypes[$account->type->value] ?? $account->type->value }}
                                    </span>
                                    {{ $account->description() }}
                                    @if ($account->is_default)
                                        <span class="badge badge-success">Padrão</span>
                                    @endif
                                </p>
                                @if ($account->label)
                                    <p class="mt-1 text-xs text-gray-500">Apelido: {{ $account->label }}</p>
                                @endif
                                @if ($account->holder_name)
                                    <p class="mt-1 text-xs text-gray-500">Titular: {{ $account->holder_name }}</p>
                                @endif
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                @unless ($account->is_default)
                                    <button type="button" class="btn btn-secondary" wire:click="setDefault({{ $account->id }})">
                                        <x-icon name="star" class="h-4 w-4" />
                                        Tornar padrão
                                    </button>
                                @endunless
                                <button type="button" class="btn btn-secondary" wire:click="openModal({{ $account->id }})">
                                    <x-icon name="pencil" class="h-4 w-4" />
                                    Editar
                                </button>
                                <button type="button" class="btn btn-danger" wire:click="confirmDelete({{ $account->id }})">
                                    <x-icon name="trash" class="h-4 w-4" />
                                    Excluir
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-8 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="credit-card" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-gray-900">Nenhuma forma de recebimento cadastrada.</p>
                    <p class="mt-1 text-sm text-gray-500">
                        Cadastre uma chave Pix ou conta bancária para poder solicitar saques.
                    </p>
                    <button type="button" class="btn btn-primary mt-4" wire:click="openModal">
                        <x-icon name="plus" class="h-4 w-4" />
                        Cadastrar agora
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Modal de cadastro/edição --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:items-center">
            <div class="absolute inset-0 bg-gray-950/60 backdrop-blur-sm" wire:click="closeModal"></div>

            <div class="relative my-8 w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl" role="dialog" aria-modal="true">
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">
                            {{ $editingId ? 'Editar forma de recebimento' : 'Nova forma de recebimento' }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">Chave Pix ou conta bancária para receber os saques.</p>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600" wire:click="closeModal" aria-label="Fechar">
                        <x-icon name="x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <form wire:submit="save">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="account-type">Tipo</label>
                                <select id="account-type" class="form-control @error('type') is-invalid @enderror"
                                    wire:model.live="type">
                                    @foreach ($accountTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('type')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="account-label">Apelido (opcional)</label>
                                <input type="text" id="account-label" class="form-control @error('label') is-invalid @enderror"
                                    wire:model="label" placeholder="Ex.: conta do dia a dia">
                                @error('label')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    @if ($type === 'pix')
                        {{-- `pixKeyType` espelha o select para a máscara da chave
                             acompanhar o tipo imediatamente (sem round-trip). --}}
                        <div class="row" x-data="{ pixKeyType: @js($pix_key_type) }">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="account-pix-type">Tipo de chave</label>
                                    <select id="account-pix-type" class="form-control @error('pix_key_type') is-invalid @enderror"
                                        wire:model="pix_key_type" x-on:change="pixKeyType = $event.target.value">
                                        <option value="">Selecione...</option>
                                        @foreach ($pixKeyTypes as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('pix_key_type')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="account-pix-key">Chave Pix</label>
                                    <input type="text" id="account-pix-key" class="form-control @error('pix_key') is-invalid @enderror"
                                        wire:model="pix_key"
                                        x-mask="pixKeyType === 'cpf' ? 'cpf' : (pixKeyType === 'cnpj' ? 'cnpj' : (pixKeyType === 'phone' ? 'pix-phone' : ''))"
                                        placeholder="CPF/CNPJ, e-mail, telefone ou chave aleatória">
                                    @error('pix_key')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="account-bank">Banco</label>
                                    <input type="text" id="account-bank" class="form-control @error('bank_name') is-invalid @enderror"
                                        wire:model="bank_name" placeholder="Ex.: Banco do Brasil">
                                    @error('bank_name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="account-bank-code">Código (opcional)</label>
                                    <input type="text" id="account-bank-code" class="form-control @error('bank_code') is-invalid @enderror"
                                        wire:model="bank_code" placeholder="001">
                                    @error('bank_code')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="account-agency">Agência</label>
                                    <input type="text" id="account-agency" class="form-control @error('agency') is-invalid @enderror"
                                        wire:model="agency">
                                    @error('agency')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="account-number">Conta</label>
                                    <input type="text" id="account-number" class="form-control @error('account') is-invalid @enderror"
                                        wire:model="account">
                                    @error('account')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="account-digit">Dígito</label>
                                    <input type="text" id="account-digit" class="form-control @error('account_digit') is-invalid @enderror"
                                        wire:model="account_digit">
                                    @error('account_digit')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="account-kind">Tipo de conta</label>
                                    <select id="account-kind" class="form-control @error('account_type') is-invalid @enderror"
                                        wire:model="account_type">
                                        @foreach ($bankAccountTypes as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('account_type')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="account-holder">Titular (opcional)</label>
                                    <input type="text" id="account-holder" class="form-control @error('holder_name') is-invalid @enderror"
                                        wire:model="holder_name">
                                    @error('holder_name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="account-holder-doc">CPF/CNPJ do titular (opcional)</label>
                                    <input type="text" id="account-holder-doc" class="form-control @error('holder_document') is-invalid @enderror"
                                        wire:model="holder_document" x-mask="document"
                                        placeholder="000.000.000-00 ou 00.000.000/0000-00">
                                    @error('holder_document')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endif

                    <label class="mb-4 flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="is_default">
                        Usar como forma de recebimento padrão
                    </label>

                    <div class="flex justify-end gap-2">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                                <x-icon name="check" class="h-4 w-4" />
                                Salvar
                            </span>
                            <span wire:loading wire:target="save" class="flex items-center gap-2">
                                <x-icon name="arrow-path" class="h-4 w-4 animate-spin" />
                                Salvando...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <x-confirm-delete
        title="Remover forma de recebimento"
        message="O histórico de saques é preservado, mas esta forma não estará mais disponível para novos saques." />
</div>
