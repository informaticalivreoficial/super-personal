<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                <x-icon name="squares-2x2" class="h-6 w-6 text-teal-600" />
                Painel de Controle
            </h1>
            <p class="mt-1 text-sm text-gray-500">Visão geral do seu trabalho</p>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('students.index') }}" wire:navigate class="card group p-5 transition hover:shadow-md">
            <div class="flex items-center gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
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
                <x-icon name="bolt" class="h-5 w-5 text-teal-600" />
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
</div>
