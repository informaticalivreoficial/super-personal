<div>
    {{-- Cabeçalho --}}
    <div class="mb-6">
        <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
            <x-icon name="banknotes" class="h-6 w-6 text-brand-600" />
            Minha assinatura
        </h1>
        <p class="mt-1 text-sm text-gray-500">Assinatura do seu plano na plataforma {{ $config->app_name ?? config('app.name') }}</p>
    </div>

    @if ($subscription)
        <div class="card">
            <div class="card-header">
                <div class="flex w-full flex-wrap items-center justify-between gap-2">
                    <h2 class="text-base font-semibold text-gray-900">Assinatura da plataforma</h2>
                    <span class="badge {{ $badgeClasses[$subscription->status->value] ?? 'badge-secondary' }}">
                        {{ $statusLabels[$subscription->status->value] ?? $subscription->status->value }}
                    </span>
                </div>
            </div>

            <div class="card-body">
                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Plano</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ $subscription->plan }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Valor mensal</dt>
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
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Fim do teste</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            {{ $subscription->trial_ends_at?->format('d/m/Y') ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wider text-gray-500">Fim da assinatura</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">
                            {{ $subscription->ends_at?->format('d/m/Y') ?? '—' }}
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

                <p class="mt-6 rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500">
                    A assinatura é gerenciada pela plataforma {{ $config->app_name ?? config('app.name') }}.
                    Para alterar plano, status ou vigência, fale com o suporte.
                </p>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand-600/10">
                    <x-icon name="banknotes" class="h-6 w-6 text-brand-600" />
                </span>
                <h2 class="mt-4 text-base font-semibold text-gray-900">Sem assinatura ativa</h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-gray-500">
                    Nenhuma assinatura cadastrada para o seu perfil. Fale com o suporte da plataforma
                    para ativar seu plano e continuar usando todos os recursos.
                </p>
            </div>
        </div>
    @endif
</div>
