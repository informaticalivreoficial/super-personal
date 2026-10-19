<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                <x-icon name="squares-2x2" class="h-6 w-6 text-brand-600" />
                Painel de Controle
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ $isAdmin ? 'Visão geral da plataforma' : 'Visão geral do seu trabalho' }}
            </p>
        </div>
    </div>

    @if ($isAdmin)
        {{-- KPIs da plataforma (admin) --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('professors.index') }}" wire:navigate class="card group p-5 transition hover:shadow-md">
                <div class="flex items-center gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                        <x-icon name="users" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-500">Treinadores (tenants)</p>
                        <p class="text-2xl font-semibold tracking-tight text-gray-900">
                            {{ $stats['teachers']['total'] }}
                        </p>
                        <p class="text-xs text-gray-400">
                            {{ $stats['teachers']['active'] }} ativo(s) · {{ $stats['teachers']['new_this_month'] }} novo(s) no mês
                        </p>
                    </div>
                </div>
            </a>

            <div class="card p-5">
                <div class="flex items-center gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                        <x-icon name="academic-cap" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-500">Alunos (plataforma)</p>
                        <p class="text-2xl font-semibold tracking-tight text-gray-900">
                            {{ $stats['students']['total'] }}
                        </p>
                        <p class="text-xs text-gray-400">{{ $stats['students']['active'] }} ativo(s)</p>
                    </div>
                </div>
            </div>

            {{-- Receita da plataforma = comissão retida em cada cobrança online
                 paga pelo aluno (não existe assinatura do treinador). --}}
            <a href="{{ route('payments.index') }}" wire:navigate class="card group p-5 transition hover:shadow-md">
                <div class="flex items-center gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <x-icon name="banknotes" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-500">Comissões no mês</p>
                        <p class="text-2xl font-semibold tracking-tight text-gray-900">
                            R$ {{ number_format($stats['commissions']['month_amount'], 2, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-400">
                            {{ $stats['commissions']['paid_count'] }} cobrança(s) online paga(s)
                            · R$ {{ number_format($stats['commissions']['total_amount'], 2, ',', '.') }} no total
                        </p>
                    </div>
                </div>
            </a>

            <a href="{{ route('payouts.admin') }}" wire:navigate class="card group p-5 transition hover:shadow-md">
                <div class="flex items-center gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <x-icon name="arrow-path" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-500">Repasses a pagar</p>
                        <p class="text-2xl font-semibold tracking-tight text-gray-900">
                            R$ {{ number_format($stats['payouts']['requested_amount'], 2, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-400">
                            {{ $stats['payouts']['requested_count'] }} solicitado(s)
                            · R$ {{ number_format($stats['payouts']['paid_amount'], 2, ',', '.') }} já repassado
                        </p>
                    </div>
                </div>
            </a>
        </div>

        {{-- Saúde da plataforma --}}
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="card p-5">
                <div class="flex items-center gap-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                        <x-icon name="calendar" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-500">Treinos hoje (plataforma)</p>
                        <p class="text-xl font-semibold tracking-tight text-gray-900">{{ $stats['trainings']['today'] }}</p>
                    </div>
                </div>
            </div>

            <div class="card p-5">
                <div class="flex items-center gap-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $stats['payments']['overdue_count'] > 0 ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600' }}">
                        <x-icon name="currency-dollar" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-500">Pagamentos em atraso</p>
                        <p class="text-xl font-semibold tracking-tight text-gray-900">{{ $stats['payments']['overdue_count'] }}</p>
                    </div>
                </div>
            </div>

            <a href="{{ route('payouts.admin') }}" wire:navigate class="card group p-5 transition hover:shadow-md">
                <div class="flex items-center gap-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                        <x-icon name="banknotes" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-500">Cobranças online pagas</p>
                        <p class="text-xl font-semibold tracking-tight text-gray-900">{{ $stats['commissions']['paid_count'] }}</p>
                    </div>
                </div>
            </a>
        </div>

        {{-- Acesso rápido (plataforma) --}}
        <div class="card mt-6">
            <div class="card-header">
                <h3 class="card-title flex items-center gap-2">
                    <x-icon name="bolt" class="h-5 w-5 text-brand-600" />
                    Acesso rápido
                </h3>
            </div>
            <div class="card-body flex flex-wrap gap-2">
                <a wire:navigate href="{{ route('professors.index') }}" class="btn btn-secondary">
                    <x-icon name="users" class="h-4 w-4" />
                    Treinadores
                </a>
                <a wire:navigate href="{{ route('professors.create') }}" class="btn btn-primary">
                    <x-icon name="user-plus" class="h-4 w-4" />
                    Novo treinador
                </a>
                <a wire:navigate href="{{ route('sports.index') }}" class="btn btn-secondary">
                    <x-icon name="fire" class="h-4 w-4" />
                    Modalidades
                </a>
                <a wire:navigate href="{{ route('payouts.admin') }}" class="btn btn-secondary">
                    <x-icon name="arrow-path" class="h-4 w-4" />
                    Repasses
                </a>
                <a wire:navigate href="{{ route('settings') }}" class="btn btn-secondary">
                    <x-icon name="cog" class="h-4 w-4" />
                    Configurações
                </a>
            </div>
        </div>
    @else
    {{-- KPIs do dia --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('students.index') }}" wire:navigate class="card group p-5 transition hover:shadow-md">
            <div class="flex items-center gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <x-icon name="academic-cap" class="h-6 w-6" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-500">Alunos ativos</p>
                    <p class="text-2xl font-semibold tracking-tight text-gray-900">
                        {{ $stats['students']['total'] }}
                    </p>
                </div>
            </div>
        </a>

        <div class="card p-5">
            <div class="flex items-center gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                    <x-icon name="calendar" class="h-6 w-6" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-500">Treinos hoje</p>
                    <p class="text-2xl font-semibold tracking-tight text-gray-900">
                        {{ $stats['trainings']['today'] }}
                    </p>
                    <p class="text-xs text-gray-400">Semana: {{ $stats['trainings']['week'] }}</p>
                </div>
            </div>
        </div>

        <a href="{{ route('plans.index') }}" wire:navigate class="card group p-5 transition hover:shadow-md">
            <div class="flex items-center gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <x-icon name="clipboard-document-list" class="h-6 w-6" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-500">Planos ativos</p>
                    <p class="text-2xl font-semibold tracking-tight text-gray-900">
                        {{ $stats['plans']['active'] }}
                    </p>
                    <p class="text-xs text-gray-400">Pendentes: {{ $stats['trainings']['pending'] }}</p>
                </div>
            </div>
        </a>

        <a href="{{ route('payments.index') }}" wire:navigate class="card group p-5 transition hover:shadow-md">
            <div class="flex items-center gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $stats['payments']['overdue_count'] > 0 ? 'bg-red-50 text-red-600' : 'bg-green-50 text-green-600' }}">
                    <x-icon name="currency-dollar" class="h-6 w-6" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-500">A receber</p>
                    <p class="text-2xl font-semibold tracking-tight text-gray-900">
                        R$ {{ number_format($stats['money']['open_amount'], 2, ',', '.') }}
                    </p>
                    @if ($stats['payments']['overdue_count'] > 0)
                        <p class="text-xs font-medium text-red-600">
                            {{ $stats['payments']['overdue_count'] }} em atraso
                            (R$ {{ number_format($stats['money']['overdue_amount'], 2, ',', '.') }})
                        </p>
                    @else
                        <p class="text-xs text-gray-400">Nenhuma cobrança em atraso</p>
                    @endif
                </div>
            </div>
        </a>
    </div>

    {{-- Dinheiro: o que entrou, o que a plataforma vai repassar e a cota manual --}}
    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <a href="{{ route('payments.index') }}" wire:navigate class="card group p-5 transition hover:shadow-md">
            <div class="flex items-center gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <x-icon name="chart-bar" class="h-6 w-6" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-500">Recebido no mês</p>
                    <p class="text-2xl font-semibold tracking-tight text-gray-900">
                        R$ {{ number_format($stats['money']['received_this_month_amount'], 2, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-400">Cobranças pagas</p>
                </div>
            </div>
        </a>

        <a href="{{ route('payouts.index') }}" wire:navigate class="card group p-5 transition hover:shadow-md">
            <div class="flex items-center gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <x-icon name="banknotes" class="h-6 w-6" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-500">Saldo para saque</p>
                    <p class="text-2xl font-semibold tracking-tight text-gray-900">
                        R$ {{ number_format($stats['money']['awaiting_payout_amount'], 2, ',', '.') }}
                    </p>
                    <p class="text-xs text-gray-400">
                        Já recebido: R$ {{ number_format($stats['money']['paid_out_total'], 2, ',', '.') }}
                        @if (($stats['money']['awaiting_release_amount'] ?? 0) > 0)
                            · A liberar: R$ {{ number_format($stats['money']['awaiting_release_amount'], 2, ',', '.') }}
                        @endif
                    </p>
                </div>
            </div>
        </a>

        <a href="{{ route('payments.create') }}" wire:navigate class="card group p-5 transition hover:shadow-md">
            <div class="flex items-center gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                    <x-icon name="document-currency-dollar" class="h-6 w-6" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-500">Cobranças manuais este mês</p>
                    {{-- Números na mesma linha de texto: a cota aparece inteira ("2 / 10"). --}}
                    <p class="text-2xl font-semibold tracking-tight text-gray-900">{{ $stats['money']['manual_this_month'] }} <span class="text-base font-normal text-gray-400">/ {{ $stats['money']['manual_limit'] }}</span></p>
                    <div class="mt-2 h-1.5 w-32 overflow-hidden rounded-full bg-gray-100">
                        <div class="h-full rounded-full {{ $stats['money']['manual_this_month'] >= $stats['money']['manual_limit'] ? 'bg-red-500' : 'bg-brand-500' }}"
                             style="width: {{ min(100, (int) round($stats['money']['manual_this_month'] / max(1, $stats['money']['manual_limit']) * 100)) }}%"></div>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">
                        @if ($stats['money']['manual_this_month'] >= $stats['money']['manual_limit'])
                            Cota do mês atingida — novas cobranças manuais só no próximo mês.
                        @else
                            Sem taxa nas cobranças manuais.
                        @endif
                    </p>
                </div>
            </div>
        </a>
    </div>

    {{-- Listas acionáveis --}}
    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
        {{-- Cobranças em atraso --}}
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h3 class="card-title flex items-center gap-2">
                    <x-icon name="clock" class="h-5 w-5 text-red-600" />
                    Cobranças em atraso
                </h3>
                <a wire:navigate href="{{ route('payments.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">
                    Ver todas
                </a>
            </div>
            <div class="card-body">
                @if ($stats['overdue_payments']->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Aluno</th>
                                    <th>Vencimento</th>
                                    <th class="text-right">Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stats['overdue_payments'] as $payment)
                                    <tr wire:key="overdue-{{ $payment->id }}">
                                        <td>
                                            <a wire:navigate href="{{ route('students.show', $payment->student_id) }}"
                                               class="font-medium text-gray-900 hover:text-brand-600">
                                                {{ $payment->student?->name ?? '—' }}
                                            </a>
                                            <p class="text-xs text-gray-400">{{ $payment->description }}</p>
                                        </td>
                                        <td class="whitespace-nowrap">
                                            {{ $payment->due_date?->format('d/m/Y') ?? '—' }}
                                            @if ($payment->due_date)
                                                <span class="badge badge-danger">
                                                    {{ (int) $payment->due_date->startOfDay()->diffInDays(now()->startOfDay(), false) }} dia(s)
                                                </span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap text-right font-medium text-gray-900">
                                            R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="flex items-center gap-2 py-6 text-sm text-gray-500">
                        <x-icon name="check-badge" class="h-5 w-5 text-green-600" />
                        Nenhuma cobrança em atraso. Tudo em dia!
                    </p>
                @endif
            </div>
        </div>

        {{-- Treinos de hoje --}}
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h3 class="card-title flex items-center gap-2">
                    <x-icon name="calendar" class="h-5 w-5 text-brand-600" />
                    Treinos de hoje
                </h3>
                <a wire:navigate href="{{ route('plans.index') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">
                    Ver planos
                </a>
            </div>
            <div class="card-body">
                @if ($stats['today_sessions']->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Aluno</th>
                                    <th>Treino</th>
                                    <th class="text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stats['today_sessions'] as $session)
                                    <tr wire:key="today-{{ $session->id }}">
                                        <td>
                                            <a wire:navigate href="{{ route('students.show', $session->student_id) }}"
                                               class="font-medium text-gray-900 hover:text-brand-600">
                                                {{ $session->student?->name ?? '—' }}
                                            </a>
                                            <p class="text-xs text-gray-400">{{ $session->sport?->name }}</p>
                                        </td>
                                        <td>
                                            {{ $session->title }}
                                            @if ($session->estimated_duration)
                                                @php($minutes = intdiv((int) $session->estimated_duration, 60))
                                                <p class="text-xs text-gray-400">
                                                    {{ $minutes >= 60 ? sprintf('%dh%02d', intdiv($minutes, 60), $minutes % 60) : $minutes.' min' }}
                                                </p>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <span class="badge {{ match ($session->status?->value) {
                                                'completed' => 'badge-success',
                                                'skipped' => 'badge-secondary',
                                                'cancelled' => 'badge-danger',
                                                default => 'badge-warning',
                                            } }}">
                                                {{ $sessionStatuses[$session->status?->value] ?? '—' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="flex items-center gap-2 py-6 text-sm text-gray-500">
                        <x-icon name="bell" class="h-5 w-5 text-gray-400" />
                        Nenhum treino agendado para hoje.
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Acesso rápido --}}
    <div class="card mt-6">
        <div class="card-header">
            <h3 class="card-title flex items-center gap-2">
                <x-icon name="bolt" class="h-5 w-5 text-brand-600" />
                Acesso rápido
            </h3>
        </div>
        <div class="card-body flex flex-wrap gap-2">
            <a wire:navigate href="{{ route('students.index') }}" class="btn btn-secondary">
                <x-icon name="academic-cap" class="h-4 w-4" />
                Alunos
            </a>
            <a wire:navigate href="{{ route('students.create') }}" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Novo aluno
            </a>
            <a wire:navigate href="{{ route('plans.create') }}" class="btn btn-secondary">
                <x-icon name="clipboard-document-list" class="h-4 w-4" />
                Novo plano
            </a>
            <a wire:navigate href="{{ route('payments.create') }}" class="btn btn-secondary">
                <x-icon name="currency-dollar" class="h-4 w-4" />
                Novo pagamento
            </a>
        </div>
    </div>
    @endif
</div>
