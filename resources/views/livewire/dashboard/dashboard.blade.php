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

            <div class="card p-5">
                <div class="flex items-center gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <x-icon name="identification" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-500">Assinaturas ativas</p>
                        <p class="text-2xl font-semibold tracking-tight text-gray-900">
                            {{ $stats['subscriptions']['active'] }}
                        </p>
                        <p class="text-xs text-gray-400">
                            {{ $stats['subscriptions']['trial'] }} em teste ·
                            {{ $stats['subscriptions']['without'] }} sem assinatura
                        </p>
                    </div>
                </div>
            </div>

            <div class="card p-5">
                <div class="flex items-center gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <x-icon name="banknotes" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-500">Receita mensal (assinaturas)</p>
                        <p class="text-2xl font-semibold tracking-tight text-gray-900">
                            R$ {{ number_format($stats['subscriptions']['monthly_amount'], 2, ',', '.') }}
                        </p>
                        <p class="text-xs text-gray-400">Soma dos planos ativos</p>
                    </div>
                </div>
            </div>
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

            <div class="card p-5">
                <div class="flex items-center gap-4">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $stats['subscriptions']['past_due'] > 0 ? 'bg-amber-50 text-amber-600' : 'bg-green-50 text-green-600' }}">
                        <x-icon name="bell" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-500">Assinaturas em atraso</p>
                        <p class="text-xl font-semibold tracking-tight text-gray-900">{{ $stats['subscriptions']['past_due'] }}</p>
                    </div>
                </div>
            </div>
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
                <a wire:navigate href="{{ route('settings') }}" class="btn btn-secondary">
                    <x-icon name="cog" class="h-4 w-4" />
                    Configurações
                </a>
            </div>
        </div>
    @else
    {{-- KPIs --}}
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
                    <p class="text-sm font-medium text-gray-500">Pagamentos pendentes</p>
                    <p class="text-2xl font-semibold tracking-tight text-gray-900">
                        R$ {{ number_format($stats['payments']['pending_amount'], 2, ',', '.') }}
                    </p>
                    @if ($stats['payments']['overdue_count'] > 0)
                        <p class="text-xs font-medium text-red-600">
                            {{ $stats['payments']['overdue_count'] }} em atraso
                        </p>
                    @endif
                </div>
            </div>
        </a>
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
