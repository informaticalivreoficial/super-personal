<div>
    <div class="card">
        <div class="card-header">
            <h2 class="text-base font-semibold text-gray-900">Assinatura da plataforma</h2>
            <p class="mt-0.5 text-xs text-gray-500">
                Billing manual: o admin controla plano, status e vigência do treinador.
            </p>
        </div>

        <form wire:submit="save" autocomplete="off">
            <div class="card-body">
                @if (! $subscriptionId)
                    <p class="mb-4 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-600">
                        Sem assinatura cadastrada — preencha abaixo para criar.
                    </p>
                @endif

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="sub_plan">Plano *</label>
                            <input type="text" class="form-control @error('plan') is-invalid @enderror"
                                id="sub_plan" wire:model="plan" placeholder="Ex.: basic, pro...">
                            @error('plan')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="sub_status">Status *</label>
                            <select id="sub_status" wire:model="status"
                                class="form-control @error('status') is-invalid @enderror">
                                @foreach ($statusLabels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="sub_amount">Valor mensal (R$)</label>
                            <input type="number" step="0.01" min="0" class="form-control @error('amount') is-invalid @enderror"
                                id="sub_amount" wire:model="amount" placeholder="Opcional">
                            @error('amount')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="sub_starts_at">Início</label>
                            <input type="date" class="form-control @error('starts_at') is-invalid @enderror"
                                id="sub_starts_at" wire:model="starts_at">
                            @error('starts_at')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="sub_trial_ends_at">Fim do teste</label>
                            <input type="date" class="form-control @error('trial_ends_at') is-invalid @enderror"
                                id="sub_trial_ends_at" wire:model="trial_ends_at">
                            @error('trial_ends_at')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="sub_ends_at">Fim da assinatura</label>
                            <input type="date" class="form-control @error('ends_at') is-invalid @enderror"
                                id="sub_ends_at" wire:model="ends_at">
                            @error('ends_at')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <p class="text-xs text-gray-500">
                    Ao cancelar (status "Cancelada"), o sistema registra automaticamente a data/hora do cancelamento.
                </p>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                        <x-icon name="check" class="h-4 w-4" />
                        {{ $subscriptionId ? 'Salvar assinatura' : 'Criar assinatura' }}
                    </span>
                    <span wire:loading wire:target="save">
                        <x-icon name="arrow-path" class="h-4 w-4 animate-spin" />
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>
