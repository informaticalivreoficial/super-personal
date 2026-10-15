<div>
    {{-- Cabeçalho --}}
    <div class="mb-6">
        <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
            <x-icon name="banknotes" class="h-6 w-6 text-brand-600" />
            Minha assinatura
        </h1>
        <p class="mt-1 text-sm text-gray-500">Sua assinatura na plataforma {{ $config->app_name ?? config('app.name') }}</p>
    </div>

    {{-- Assinatura atual --}}
    @if ($subscription)
        <div class="card mb-6">
            <div class="card-header">
                <div class="flex w-full flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-semibold text-gray-900">Assinatura atual</h2>
                    <span class="badge {{ $badgeClasses[$subscription->status->value] ?? 'badge-secondary' }}">
                        {{ $statusLabels[$subscription->status->value] ?? $subscription->status->value }}
                    </span>
                </div>
            </div>

            <div class="card-body">
                @if ($subscription->status->value === 'pending')
                    <p class="mb-4 rounded-lg bg-sky-50 px-3 py-2 text-sm text-sky-800">
                        Aguardando pagamento. Escolha o plano e o período abaixo novamente para
                        enviar o pagamento (cartão ou Pix).
                    </p>
                @endif

                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Plano</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $subscription->plan?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Período</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            {{ $periodLabels[$subscription->period?->value] ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Valor da assinatura</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            @if ($subscription->amount !== null)
                                R$ {{ number_format((float) $subscription->amount, 2, ',', '.') }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Início</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            {{ $subscription->starts_at?->format('d/m/Y') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Fim da vigência</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            {{ $subscription->ends_at?->format('d/m/Y') ?? 'Sem término' }}
                        </dd>
                    </div>
                    @if ($subscription->cancelled_at)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Cancelada em</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">
                                {{ $subscription->cancelled_at->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    @else
        <div class="card mb-6">
            <div class="card-body text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-600/10">
                    <x-icon name="banknotes" class="h-6 w-6 text-brand-600" />
                </span>
                <h2 class="mt-4 text-base font-semibold text-gray-900">Sem assinatura</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">
                    Escolha um dos planos abaixo para continuar usando todos os recursos.
                </p>
            </div>
        </div>
    @endif

    {{-- Catálogo de planos --}}
    <div class="mb-4">
        <h2 class="text-base font-semibold text-gray-900">Planos disponíveis</h2>
        <p class="mt-1 text-sm text-gray-500">Pagamento com cartão ou Pix direto aqui no sistema, sem sair da página. Planos gratuitos ativam na hora.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($plans as $plan)
            @php
                $isCurrent = $subscription && $subscription->plan_id === $plan->id;
            @endphp
            <div class="card {{ $isCurrent ? 'ring-2 ring-brand-500' : '' }}">
                <div class="card-body flex h-full flex-col">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $plan->name }}</h3>
                        @if ($isCurrent)
                            <span class="badge badge-brand">Plano atual</span>
                        @elseif ($plan->isFree())
                            <span class="badge badge-success">Gratuito</span>
                        @endif
                    </div>

                    @if ($plan->description)
                        <p class="mt-2 text-sm text-gray-500">{{ $plan->description }}</p>
                    @endif

                    <p class="mt-3 text-sm text-gray-700">
                        <strong>{{ $plan->maxStudentsLabel() }}</strong> aluno(s)
                    </p>

                    <dl class="mt-3 space-y-1 text-sm text-gray-600">
                        <div class="flex justify-between">
                            <dt>Mensal</dt>
                            <dd class="font-medium text-gray-900">R$ {{ number_format((float) $plan->price_monthly, 2, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Trimestral</dt>
                            <dd class="font-medium text-gray-900">R$ {{ number_format((float) $plan->price_quarterly, 2, ',', '.') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Anual</dt>
                            <dd class="font-medium text-gray-900">R$ {{ number_format((float) $plan->price_annual, 2, ',', '.') }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4 flex flex-wrap gap-2 pt-2">
                        @if ($plan->isFree())
                            <button type="button" wire:click="subscribe({{ $plan->id }}, 'monthly')"
                                wire:loading.attr="disabled"
                                class="btn btn-primary btn-sm flex-1"
                                @if ($isCurrent) disabled @endif>
                                {{ $isCurrent ? 'Plano atual' : 'Usar plano gratuito' }}
                            </button>
                        @else
                            @foreach ($periodLabels as $periodValue => $periodLabel)
                                <button type="button" wire:click="subscribe({{ $plan->id }}, '{{ $periodValue }}')"
                                    wire:loading.attr="disabled"
                                    class="btn {{ $isCurrent && $subscription->period?->value === $periodValue ? 'btn-secondary' : 'btn-primary' }} btn-sm flex-1"
                                    @if ($isCurrent && $subscription->period?->value === $periodValue && $subscription->status->value !== 'pending') disabled @endif>
                                    {{ $isCurrent && $subscription->period?->value === $periodValue && $subscription->status->value !== 'pending' ? $periodLabel : $periodLabel }}
                                </button>
                            @endforeach
                        @endif
                    </div>

                    @if ($isCurrent && $subscription->status->value === 'pending')
                        <p class="mt-2 text-xs text-sky-700">
                            Clique em um período acima para enviar o pagamento novamente.
                        </p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <p class="mt-6 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500">
        Precisa de ajuda ou de um plano personalizado? Fale com o suporte da plataforma.
    </p>

    {{-- Checkout transparente: cartão criptografado no navegador ou Pix (QR Code aqui) --}}
    @if ($showPaymentForm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true"
            aria-label="Pagamento da assinatura">
            <div class="absolute inset-0 bg-gray-950/60" wire:click="cancelPayment"></div>

            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Finalizar assinatura</h3>
                        <p class="mt-0.5 text-sm text-gray-500">
                            {{ $payPlanName }} — {{ $periodLabels[$payPeriod] ?? $payPeriod }} —
                            <strong class="text-gray-900">R$ {{ number_format($payAmount, 2, ',', '.') }}</strong>
                        </p>
                    </div>
                    <button type="button" wire:click="cancelPayment" aria-label="Fechar"
                        class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                        <x-icon name="x-mark" class="h-5 w-5" />
                    </button>
                </div>

                <div class="px-5 py-4">
                    {{-- Abas: cartão | pix --}}
                    <div class="mb-4 grid grid-cols-2 gap-1 rounded-lg bg-gray-100 p-1">
                        <button type="button" wire:click="setPayMethod('card')"
                            class="rounded-md px-3 py-1.5 text-sm font-medium transition {{ $payMethod === 'card' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                            Cartão
                        </button>
                        <button type="button" wire:click="setPayMethod('pix')"
                            class="rounded-md px-3 py-1.5 text-sm font-medium transition {{ $payMethod === 'pix' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">
                            Pix
                        </button>
                    </div>

                    {{--
                        Os dois painéis ficam SEMPRE no DOM: a aba alterna apenas a
                        classe de visibilidade. Trocar os blocos por @if fazia o morph do
                        Livewire reaproveitar o <div> do cartão (mesma posição na árvore) e
                        o `wire:ignore` preservar o DOM antigo — clicar em "Pix" mantinha o
                        formulário do cartão na tela. Bônus: os dados digitados no cartão
                        não se perdem ao alternar as abas.
                    --}}
                    <div id="pay-panel-card" @class(['hidden' => $payMethod !== 'card'])>
                        <div class="mb-4">
                            <label for="pay-cpf" class="mb-1 block text-sm font-medium text-gray-700">
                                CPF do titular
                            </label>
                            <input type="text" id="pay-cpf" wire:model="cpf" x-mask="cpf" inputmode="numeric"
                                maxlength="14" placeholder="000.000.000-00" autocomplete="off"
                                class="form-control">
                            @error('cpf')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        {{--
                            wire:ignore: os campos são lidos pelo JS (Alpine) no envio —
                            sem isso o morph do Livewire limparia o que o usuário digitou
                            depois de uma recusa.
                        --}}
                        <div wire:ignore x-data="cardPayment({
                                publicKey: @js($pagbankPublicKey),
                                amount: {{ $payAmount }},
                                cpfId: 'pay-cpf',
                            })">
                            <div class="space-y-3">
                                <div>
                                    <label for="pay-card-number" class="mb-1 block text-sm font-medium text-gray-700">
                                        Número do cartão
                                    </label>
                                    <input type="text" id="pay-card-number" x-model="number" x-mask="card"
                                        inputmode="numeric" autocomplete="cc-number" placeholder="0000 0000 0000 0000"
                                        class="form-control">
                                </div>

                                <div>
                                    <label for="pay-card-holder" class="mb-1 block text-sm font-medium text-gray-700">
                                        Nome impresso no cartão
                                    </label>
                                    <input type="text" id="pay-card-holder" x-model="holder" autocomplete="cc-name"
                                        placeholder="Como está no cartão" class="form-control">
                                </div>

                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label for="pay-card-month" class="mb-1 block text-sm font-medium text-gray-700">
                                            Mês
                                        </label>
                                        <select id="pay-card-month" x-model="month" class="form-control">
                                            <option value="">--</option>
                                            <template x-for="m in months" :key="m">
                                                <option :value="m" x-text="m"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="pay-card-year" class="mb-1 block text-sm font-medium text-gray-700">
                                            Ano
                                        </label>
                                        <select id="pay-card-year" x-model="year" class="form-control">
                                            <option value="">----</option>
                                            <template x-for="y in years" :key="y">
                                                <option :value="y" x-text="y"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="pay-card-cvv" class="mb-1 block text-sm font-medium text-gray-700">
                                            CVV
                                        </label>
                                        <input type="text" id="pay-card-cvv" x-model="cvv" x-mask="cvv"
                                            inputmode="numeric" maxlength="4" autocomplete="cc-csc" placeholder="123"
                                            class="form-control">
                                    </div>
                                </div>

                                <div>
                                    <label for="pay-card-installments" class="mb-1 block text-sm font-medium text-gray-700">
                                        Parcelamento
                                    </label>
                                    <select id="pay-card-installments" x-model="installments" class="form-control">
                                        <template x-for="option in installmentOptions" :key="option.n">
                                            <option :value="option.n" x-text="option.label"></option>
                                        </template>
                                    </select>
                                </div>
                            </div>

                            <p class="mt-3 flex items-start gap-1.5 text-xs text-gray-500">
                                <x-icon name="shield-check" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                Seus dados do cartão são criptografados no seu navegador e processados com
                                segurança pelo nosso parceiro de pagamentos — não ficam armazenados neste sistema.
                            </p>

                            <p x-show="error" x-cloak x-text="error"
                                class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700"></p>

                            <button type="button" x-on:click="pay()" x-bind:disabled="processing"
                                class="btn btn-primary btn-sm mt-4 w-full">
                                <span x-show="!processing">Pagar R$ {{ number_format($payAmount, 2, ',', '.') }}</span>
                                <span x-show="processing" x-cloak>Processando…</span>
                            </button>
                        </div>
                    </div>

                    <div id="pay-panel-pix" @class(['hidden' => $payMethod !== 'pix'])>
                        @if (! $pixGenerated)
                            <div class="text-center">
                                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-600/10">
                                    <x-icon name="solid-qr-code" class="h-6 w-6 text-brand-600" />
                                </span>
                                <p class="mx-auto mt-3 max-w-sm text-sm text-gray-500">
                                    Gere o QR Code e pague pelo app do seu banco. A confirmação é
                                    automática — a assinatura é ativada assim que o pagamento cair.
                                </p>
                                <button type="button" wire:click="payWithPix" wire:loading.attr="disabled"
                                    class="btn btn-primary btn-sm mt-4">
                                    <span wire:loading.remove wire:target="payWithPix">Gerar QR Code Pix</span>
                                    <span wire:loading wire:target="payWithPix">Gerando…</span>
                                </button>
                            </div>
                        @else
                            {{-- `copied` e o $refs do código vivem no mesmo escopo Alpine:
                                 com o x-data no próprio textarea, o botão (irmão) não
                                 enxergava o $refs e o copiar não funcionava. --}}
                            <div class="text-center" x-data="{ copied: false }">
                                @if ($pixQrCode)
                                    {{-- SVG gerado localmente a partir do código — o modal
                                         não carrega imagem de domínio externo. --}}
                                    <div class="mx-auto flex h-48 w-48 items-center justify-center rounded-lg border border-gray-200 bg-white p-1 [&>svg]:h-full [&>svg]:w-full">
                                        {!! $pixQrCode !!}
                                    </div>
                                @endif

                                <p class="mt-3 text-sm text-gray-500">
                                    Abra o app do seu banco, escolha pagar com Pix e leia o QR Code
                                    (ou use o código abaixo).
                                </p>

                                <textarea readonly rows="3" x-ref="pixCode"
                                    class="form-control mt-3 font-mono text-xs">{{ $pixCode }}</textarea>

                                <div class="mt-3 flex flex-wrap justify-center gap-2">
                                    <button type="button"
                                        x-on:click="navigator.clipboard.writeText($refs.pixCode.value); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="btn btn-secondary btn-sm">
                                        <span x-show="!copied">Copiar código Pix</span>
                                        <span x-show="copied" x-cloak>Código copiado!</span>
                                    </button>

                                    <button type="button" wire:click="checkPayment" wire:loading.attr="disabled"
                                        class="btn btn-primary btn-sm">
                                        <span wire:loading.remove wire:target="checkPayment">Já efetuei o pagamento</span>
                                        <span wire:loading wire:target="checkPayment">Confirmando…</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex justify-end border-t border-gray-100 bg-gray-50 px-5 py-3">
                    <button type="button" wire:click="cancelPayment" class="btn btn-secondary btn-sm">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
