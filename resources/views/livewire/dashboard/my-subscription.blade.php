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
                        Aguardando pagamento. Escolha o plano e o período abaixo novamente para gerar
                        um novo link de pagamento (Mercado Pago), se necessário.
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
        <p class="mt-1 text-sm text-gray-500">Pagamento seguro via Mercado Pago. Planos gratuitos ativam na hora.</p>
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
                            Clique em um período acima para (re)gerar o link de pagamento.
                        </p>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <p class="mt-6 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500">
        Precisa de ajuda ou de um plano personalizado? Fale com o suporte da plataforma.
    </p>
</div>
