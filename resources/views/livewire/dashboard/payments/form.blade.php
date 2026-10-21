<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('payments.index') }}" title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="currency-dollar" class="h-6 w-6 text-brand-600" />
                    {{ $isEdit ? 'Editar pagamento' : 'Novo pagamento' }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">Pagamentos / {{ $isEdit ? 'Editar' : 'Novo' }}</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title flex items-center gap-2">
                <x-icon name="currency-dollar" class="h-5 w-5 text-brand-600" />
                Dados do pagamento
            </h3>
        </div>

        <form wire:submit="save">
            <div class="card-body">
                <div class="row">
                    @if ($isEdit)
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Aluno</label>
                                <input type="text" class="form-control" value="{{ $paymentStudent?->name ?? '—' }}" readonly>
                                <small class="text-muted">O aluno do pagamento não pode ser alterado.</small>
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
                            <label for="description">Descrição *</label>
                            <input type="text" class="form-control @error('description') is-invalid @enderror"
                                id="description" wire:model="description"
                                placeholder="Ex.: Mensalidade outubro/2026">
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="amount">Valor (R$) *</label>
                            <input type="number" step="0.01" min="0.01"
                                class="form-control @error('amount') is-invalid @enderror"
                                id="amount" wire:model="amount" placeholder="Ex.: 350.00"
                                @disabled($onlinePaid || $locked)>
                            @error('amount')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="due_date">Vencimento *</label>
                            <input type="date" class="form-control @error('due_date') is-invalid @enderror"
                                id="due_date" wire:model="due_date">
                            @error('due_date')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control @error('status') is-invalid @enderror"
                                id="status" wire:model="status" @disabled($onlinePaid || $locked)>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="channel">Canal de cobrança *</label>
                            <select class="form-control @error('channel') is-invalid @enderror"
                                id="channel" wire:model.live="channel" @disabled($onlinePaid || $locked)>
                                @foreach ($channels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('channel')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="payment_method">Forma de pagamento</label>
                            <select class="form-control @error('payment_method') is-invalid @enderror"
                                id="payment_method" wire:model="payment_method">
                                <option value="">Selecione...</option>
                                @foreach ($methods as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('payment_method')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="paid_at">Data do pagamento</label>
                            <input type="date" class="form-control @error('paid_at') is-invalid @enderror"
                                id="paid_at" wire:model="paid_at" @disabled($onlinePaid || $locked)>
                            <small class="text-muted">Preenchido automaticamente ao marcar como pago.</small>
                            @error('paid_at')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="notes">Observações</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror"
                                id="notes" rows="3" wire:model="notes"
                                placeholder="Ex.: Pagamento combinado via Pix..."></textarea>
                            @error('notes')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    @if ($channel === 'online')
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <x-icon name="information-circle" class="h-5 w-5" />
                                <span>
                                    Cobrança <strong>online</strong>: o aluno paga pelo app (Pix) e o valor cai na conta
                                    da plataforma. Você recebe o <strong>líquido</strong> (valor menos a taxa de
                                    {{ number_format(\App\Models\Config::feePercent(), 2, ',', '.') }}%) no repasse.
                                </span>
                            </div>
                        </div>
                    @endif

                    @if ($locked)
                        <div class="col-md-12">
                            <div class="alert alert-warning">
                                <x-icon name="exclamation-triangle" class="h-5 w-5" />
                                <span>
                                    Esta cobrança já faz parte de um repasse e não pode mais ser alterada.
                                </span>
                            </div>
                        </div>
                    @elseif ($onlinePaid)
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <x-icon name="information-circle" class="h-5 w-5" />
                                <span>
                                    Cobrança online paga: valor, canal e situação são controlados pelo gateway.
                                    Você pode corrigir a descrição e as observações.
                                </span>
                            </div>
                        </div>
                    @endif

                    @if ($isEdit && $payment?->isOnline() && $payment?->fee_amount !== null)
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Repasse desta cobrança</label>
                                <input type="text" class="form-control" readonly
                                    value="Taxa: R$ {{ number_format((float) $payment->fee_amount, 2, ',', '.') }} · Líquido: R$ {{ number_format((float) $payment->net_amount, 2, ',', '.') }}">
                                <small class="text-muted">
                                    Status no gateway: {{ $payment->gateway_status ?: '—' }}.
                                    O repasse é acompanhado em "Financeiro".
                                </small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card-footer flex flex-wrap gap-2">
                @if (! $locked)
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save" class="flex items-center gap-2">
                        <x-icon name="check" class="h-4 w-4" />
                        {{ $isEdit ? 'Salvar alterações' : 'Registrar pagamento' }}
                    </span>
                    <span wire:loading wire:target="save" class="flex items-center gap-2">
                        <x-icon name="arrow-path" class="h-4 w-4 animate-spin" />
                        Salvando...
                    </span>
                </button>
                @endif
                <button type="button" class="btn btn-secondary" wire:click="cancel">
                    <x-icon name="x-mark" class="h-4 w-4" />
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>
