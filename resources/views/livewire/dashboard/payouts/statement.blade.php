<div>
    @php
        $toneClasses = [
            'success' => 'bg-emerald-100 text-emerald-700',
            'warning' => 'bg-amber-100 text-amber-700',
            'danger' => 'bg-red-100 text-red-700',
            'neutral' => 'bg-gray-100 text-gray-600',
        ];
    @endphp

    {{-- Indicadores do período --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="card">
            <div class="card-body">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Entradas</p>
                <p class="mt-1 text-2xl font-semibold text-emerald-600">
                    R$ {{ number_format($totals['credits'], 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Líquido das cobranças online pagas</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Saídas</p>
                <p class="mt-1 text-2xl font-semibold text-red-600">
                    R$ {{ number_format($totals['debits'], 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Saques solicitados no período</p>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Saldo do período</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">
                    R$ {{ number_format($totals['net'], 2, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-gray-500">Entradas menos saídas</p>
            </div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="mb-4 flex flex-wrap gap-2">
                <button type="button" class="btn btn-secondary" wire:click="applyPreset('month')">Este mês</button>
                <button type="button" class="btn btn-secondary" wire:click="applyPreset('last_month')">Mês passado</button>
                <button type="button" class="btn btn-secondary" wire:click="applyPreset('last_30')">Últimos 30 dias</button>
                <button type="button" class="btn btn-secondary" wire:click="applyPreset('year')">Este ano</button>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="statement-from">De</label>
                        <input type="date" id="statement-from" class="form-control" wire:model.live="from">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="statement-to">Até</label>
                        <input type="date" id="statement-to" class="form-control" wire:model.live="to">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="statement-type">Tipo</label>
                        <select id="statement-type" class="form-control" wire:model.live="type">
                            @foreach ($typeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-secondary w-full" wire:click="clearFilters">
                            <x-icon name="arrow-path" class="h-4 w-4" />
                            Limpar filtros
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Movimentações --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title flex items-center gap-2">
                <x-icon name="list-bullet" class="h-5 w-5 text-brand-600" />
                Movimentações
            </h3>
        </div>

        <div class="card-body table-responsive">
            @if ($entries->isNotEmpty())
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Movimentação</th>
                            <th>Tipo</th>
                            <th class="text-center">Situação</th>
                            <th class="text-right">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($entries as $entry)
                            <tr wire:key="statement-{{ $entry['kind'] }}-{{ $entry['occurred_at']?->timestamp }}-{{ $loop->index }}">
                                <td>{{ $entry['occurred_at']?->format('d/m/Y') ?? '—' }}</td>
                                <td>
                                    <span class="font-medium text-gray-900">{{ $entry['title'] }}</span>
                                    @if ($entry['description'])
                                        <span class="block text-xs text-gray-500">{{ $entry['description'] }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $entry['kind'] === 'credit' ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $entry['kind'] === 'credit' ? 'Entrada' : 'Saída' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $toneClasses[$entry['tone']] ?? $toneClasses['neutral'] }}">
                                        {{ $entry['status_label'] }}
                                    </span>
                                </td>
                                <td class="text-right font-semibold {{ $entry['kind'] === 'credit' ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $entry['kind'] === 'credit' ? '+' : '-' }}
                                    R$ {{ number_format($entry['amount'], 2, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $entries->links() }}
                </div>
            @else
                <div class="py-8 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="list-bullet" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-gray-900">Nenhuma movimentação no período.</p>
                    <p class="mt-1 text-sm text-gray-500">
                        Ajuste o período ou aguarde cobranças online serem pagas pelos alunos.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
