<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                <x-icon name="banknotes" class="h-6 w-6 text-brand-600" />
                Assinaturas
            </h1>
            <p class="mt-1 text-sm text-gray-500">Cobrança dos treinadores à plataforma — planos em
                <a href="{{ route('saas-plans.index') }}" class="font-semibold text-brand-600 hover:text-brand-700">gerenciar planos</a>
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="flex w-full flex-wrap items-center gap-3">
                <div class="w-full max-w-xs">
                    <div class="input-group input-group-sm">
                        <input type="text" wire:model.live.debounce.400ms="search"
                            class="form-control" placeholder="Buscar treinador...">
                        @if ($search)
                            <div class="input-group-append">
                                <button type="button" class="btn btn-default btn-sm"
                                    wire:click="$set('search', '')" title="Limpar busca">
                                    <x-icon name="x-mark" class="h-4 w-4" />
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="w-52">
                    <select wire:model.live="statusFilter" class="form-control form-control-sm">
                        <option value="">Todos os status</option>
                        @foreach ($statusLabels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body table-responsive">
            @if ($subscriptions->count() > 0)
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Treinador</th>
                            <th>Plano</th>
                            <th class="text-center">Período</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Valor</th>
                            <th class="text-center">Gateway</th>
                            <th class="text-center">Vigência</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subscriptions as $subscription)
                            <tr wire:key="sub-{{ $subscription->id }}">
                                <td>
                                    <span class="font-semibold text-gray-900">{{ $subscription->teacher?->name ?? '—' }}</span>
                                    <br><small class="text-muted">{{ $subscription->teacher?->user?->email }}</small>
                                </td>
                                <td>{{ $subscription->plan?->name ?? '—' }}</td>
                                <td class="text-center">
                                    {{ \App\Enums\BillingPeriod::labels()[$subscription->period?->value] ?? '—' }}
                                </td>
                                <td class="text-center">
                                    @php
                                        $badge = match ($subscription->status->value) {
                                            'active' => 'badge-success',
                                            'pending' => 'badge-info',
                                            'trial' => 'badge-info',
                                            'past_due' => 'badge-warning',
                                            'cancelled', 'expired' => 'badge-secondary',
                                            default => 'badge-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }}">
                                        {{ $statusLabels[$subscription->status->value] ?? $subscription->status->value }}
                                    </span>
                                </td>
                                <td class="text-right">R$ {{ number_format((float) $subscription->amount, 2, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $subscription->gateway === 'manual' ? 'badge-secondary' : 'badge-info' }}">
                                        {{ $subscription->gateway === 'manual' ? 'Manual' : 'Online' }}
                                    </span>
                                </td>
                                <td class="text-center text-sm">
                                    {{ $subscription->ends_at?->format('d/m/Y') ?? 'Sem término' }}
                                </td>
                                <td>
                                    <div class="flex items-center justify-center">
                                        <a wire:navigate href="{{ route('professors.edit', $subscription->teacher_id) }}"
                                            class="btn btn-xs btn-secondary" title="Gerenciar assinatura">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="card-footer">
                    {{ $subscriptions->links() }}
                </div>
            @else
                <div class="py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="banknotes" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-gray-900">
                        @if ($search || $statusFilter)
                            Nenhuma assinatura encontrada para os filtros informados.
                        @else
                            Nenhuma assinatura cadastrada ainda.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
