<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('saas-plans.index') }}" title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="currency-dollar" class="h-6 w-6 text-brand-600" />
                    {{ $isEdit ? 'Editar' : 'Cadastrar' }} plano
                </h1>
                <p class="mt-1 text-sm text-gray-500">Planos do SaaS / {{ $isEdit ? 'Editar' : 'Novo' }}</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" autocomplete="off">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="plan_name">Nome *</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="plan_name" wire:model="name" placeholder="Ex.: Intermediário">
                            @error('name')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="plan_slug">Slug</label>
                            <input type="text" class="form-control @error('slug') is-invalid @enderror"
                                id="plan_slug" wire:model="slug" placeholder="Gerado do nome se vazio">
                            @error('slug')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="plan_max_students">Limite de alunos</label>
                            <input type="number" min="1" class="form-control @error('max_students') is-invalid @enderror"
                                id="plan_max_students" wire:model="max_students" placeholder="Vazio = ilimitado">
                            @error('max_students')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="plan_price_monthly">Preço mensal (R$)</label>
                            <input type="number" step="0.01" min="0" class="form-control @error('price_monthly') is-invalid @enderror"
                                id="plan_price_monthly" wire:model="price_monthly" placeholder="0 = gratuito">
                            @error('price_monthly')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="plan_price_quarterly">Preço trimestral (R$)</label>
                            <input type="number" step="0.01" min="0" class="form-control @error('price_quarterly') is-invalid @enderror"
                                id="plan_price_quarterly" wire:model="price_quarterly" placeholder="0 = gratuito">
                            @error('price_quarterly')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="plan_price_annual">Preço anual (R$)</label>
                            <input type="number" step="0.01" min="0" class="form-control @error('price_annual') is-invalid @enderror"
                                id="plan_price_annual" wire:model="price_annual" placeholder="0 = gratuito">
                            @error('price_annual')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="plan_sort_order">Ordem de exibição</label>
                            <input type="number" min="0" class="form-control @error('sort_order') is-invalid @enderror"
                                id="plan_sort_order" wire:model="sort_order">
                            @error('sort_order')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="plan_active">Status</label>
                            <div class="mt-2 flex items-center gap-2">
                                <input type="checkbox" id="plan_active" wire:model="active" class="h-4 w-4 rounded border-gray-300 text-brand-600">
                                <label for="plan_active" class="text-sm text-gray-700">Ativo (disponível para assinatura)</label>
                            </div>
                            @error('active')
                                <span class="text-sm text-red-500">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group mb-0">
                            <label for="plan_description">Descrição</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                id="plan_description" rows="3" wire:model="description"
                                placeholder="Descreva o plano (aparece para o treinador na hora de assinar)"></textarea>
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer flex items-center justify-end gap-2">
                <button type="button" wire:click="cancel" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <x-icon name="save" class="h-4 w-4" />
                    Salvar
                </button>
            </div>
        </div>
    </form>
</div>
