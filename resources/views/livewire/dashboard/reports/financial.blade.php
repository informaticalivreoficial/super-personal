<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                <x-icon name="chart-bar" class="h-6 w-6 text-brand-600" />
                Relatório Financeiro
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Cobranças dos alunos no período
                <span class="text-gray-400">(competência: data de vencimento)</span>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a wire:navigate href="{{ route('payments.index') }}" class="btn btn-secondary">
                <x-icon name="currency-dollar" class="h-4 w-4" />
                Pagamentos
            </a>
            <a href="{{ $exportUrl }}" class="btn btn-primary">
                <x-icon name="arrow-down-tray" class="h-4 w-4" />
                Exportar CSV
            </a>
        </div>
    </div>

    {{-- Período e filtros --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="mb-4 flex flex-wrap items-center gap-2">
                @foreach ($periods as $key => $label)
                    <button type="button" wire:click="setPeriod('{{ $key }}')"
                        @class([
                            'btn btn-sm',
                            $period === $key ? 'btn-primary' : 'btn-secondary',
                        ])>
                        {{ $label }}
                    </button>
                @endforeach
                @if ($period === 'custom')
                    <span class="badge badge-info">Período personalizado</span>
                @endif
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group mb-md-0">
                        <label for="from">De</label>
                        <input type="date" id="from" class="form-control" wire:model.live="from">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-md-0">
                        <label for="to">Até</label>
                        <input type="date" id="to" class="form-control" wire:model.live="to">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-md-0">
                        <label for="filter_student">Aluno</label>
                        <select id="filter_student" class="form-control" wire:model.live="filterStudentId">
                            <option value="">Todos</option>
                            @foreach ($studentOptions as $student)
                                <option value="{{ $student->id }}">{{ $student->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-md-0">
                        <label for="filter_channel">Canal</label>
                        <select id="filter_channel" class="form-control" wire:model.live="filterChannel">
                            <option value="">Todos</option>
                            @foreach ($channels as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-md-0">
                        <label for="filter_status">Situação</label>
                        <select id="filter_status" class="form-control" wire:model.live="filterStatus">
                            <option value="">Todas</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                <p class="mb-0 text-sm text-gray-500">
                    Período apurado:
                    <strong class="text-gray-700">
                        {{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d/m/Y') }}
                        a
                        {{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d/m/Y') }}
                    </strong>
                </p>
                <button type="button" class="btn btn-secondary btn-sm" wire:click="clearFilters">
                    <x-icon name="x-mark" class="h-4 w-4" /> Limpar filtros
                </button>
            </div>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card">
            <div class="card-body flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-100 text-brand-700">
                    <x-icon name="check-badge" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Recebido no período</p>
                    <p class="text-lg font-semibold text-gray-900">
                        R$ {{ number_format($summary['received'], 2, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-500">
                        Líquido R$ {{ number_format($summary['net'], 2, ',', '.') }}
                        · taxas R$ {{ number_format($summary['fees'], 2, ',', '.') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                    <x-icon name="clock" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">A receber</p>
                    <p class="text-lg font-semibold text-gray-900">
                        R$ {{ number_format($summary['open_amount'], 2, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-500">{{ $summary['count_open'] }} cobrança(s) pendente(s)</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100 text-red-700">
                    <x-icon name="exclamation-triangle" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Em atraso</p>
                    <p class="text-lg font-semibold text-red-600">
                        R$ {{ number_format($summary['overdue_amount'], 2, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-500">{{ $summary['count_overdue'] }} cobrança(s) em atraso</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                    <x-icon name="document-text" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Cobranças no período</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $summary['count'] }}</p>
                    <p class="text-xs text-gray-500">
                        {{ $summary['count_manual'] }} manual(is) · {{ $summary['count_online'] }} online
                        · {{ $summary['count_paid'] }} paga(s)
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Quebra por mês --}}
    @if (count($months) > 1)
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title flex items-center gap-2">
                    <x-icon name="chart-bar" class="h-5 w-5 text-brand-600" />
                    Recebido por mês
                </h3>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Mês</th>
                            <th class="text-center">Cobranças</th>
                            <th class="text-right">Recebido</th>
                            <th class="text-right">Taxas</th>
                            <th class="text-right">Líquido</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($months as $month)
                            <tr wire:key="month-{{ $month['month'] }}">
                                <td class="font-medium text-gray-900">{{ ucfirst($month['label']) }}</td>
                                <td class="text-center">{{ $month['count'] }}</td>
                                <td class="text-right">R$ {{ number_format($month['received'], 2, ',', '.') }}</td>
                                <td class="text-right text-gray-500">R$ {{ number_format($month['fees'], 2, ',', '.') }}</td>
                                <td class="text-right font-medium text-gray-900">R$ {{ number_format($month['net'], 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Recebido por aluno --}}
    @if (count($topStudents) > 0)
        <div class="card mb-6">
            <div class="card-header">
                <h3 class="card-title flex items-center gap-2">
                    <x-icon name="users" class="h-5 w-5 text-brand-600" />
                    Recebido por aluno
                </h3>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th class="text-center">Cobranças pagas</th>
                            <th class="text-right">Recebido</th>
                            <th class="text-right">Líquido</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topStudents as $student)
                            <tr wire:key="student-{{ $student['student_id'] }}">
                                <td>
                                    @if ($student['student_id'])
                                        <a wire:navigate href="{{ route('students.show', $student['student_id']) }}"
                                            class="font-medium text-brand-600 hover:text-brand-700">
                                            {{ $student['name'] }}
                                        </a>
                                    @else
                                        {{ $student['name'] }}
                                    @endif
                                </td>
                                <td class="text-center">{{ $student['count'] }}</td>
                                <td class="text-right">R$ {{ number_format($student['received'], 2, ',', '.') }}</td>
                                <td class="text-right font-medium text-gray-900">R$ {{ number_format($student['net'], 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Cobranças do período --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title flex items-center gap-2">
                <x-icon name="currency-dollar" class="h-5 w-5 text-brand-600" />
                Cobranças do período
            </h3>
            <span class="text-sm text-gray-500">{{ $payments->total() }} registro(s)</span>
        </div>

        <div class="card-body table-responsive">
            @if ($payments->count() > 0)
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Vencimento</th>
                            <th>Aluno</th>
                            <th>Descrição</th>
                            <th class="text-center">Canal</th>
                            <th class="text-center">Situação</th>
                            <th class="text-right">Valor</th>
                            <th class="text-right">Taxa</th>
                            <th class="text-right">Líquido</th>
                            <th class="text-center">Pago em</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr wire:key="payment-{{ $payment->id }}">
                                <td>{{ $payment->due_date?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $payment->student?->name ?? '—' }}</td>
                                <td>{{ $payment->description }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $payment->isOnline() ? 'badge-info' : 'badge-secondary' }}">
                                        {{ $channels[$payment->channel?->value] ?? '—' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @php
                                        $badgeClass = match ($payment->status?->value) {
                                            'paid' => 'badge-success',
                                            'overdue' => 'badge-danger',
                                            'cancelled' => 'badge-secondary',
                                            default => 'badge-warning',
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">
                                        {{ $statuses[$payment->status?->value] ?? '—' }}
                                    </span>
                                </td>
                                <td class="text-right">R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}</td>
                                <td class="text-right text-gray-500">
                                    {{ $payment->fee_amount !== null ? 'R$ '.number_format((float) $payment->fee_amount, 2, ',', '.') : '—' }}
                                </td>
                                <td class="text-right font-medium text-gray-900">
                                    {{ $payment->net_amount !== null ? 'R$ '.number_format((float) $payment->net_amount, 2, ',', '.') : '—' }}
                                </td>
                                <td class="text-center">{{ $payment->paid_at?->format('d/m/Y') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="card-footer">
                    {{ $payments->links() }}
                </div>
            @else
                <div class="py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                        <x-icon name="chart-bar" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-sm font-medium text-gray-900">
                        @if ($filters['student_id'] || $filters['channel'] || $filters['status'])
                            Nenhuma cobrança encontrada com os filtros atuais.
                        @else
                            Nenhuma cobrança no período selecionado.
                        @endif
                    </p>
                    <p class="mt-1 text-sm text-gray-500">
                        Ajuste o período ou os filtros para ver outros lançamentos.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
