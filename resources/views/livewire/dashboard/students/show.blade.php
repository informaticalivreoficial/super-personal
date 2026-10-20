@php
    $level = $levels[$student->fitness_level?->value] ?? null;

    $personal = [
        'E-mail' => $student->email ?: '—',
        'Telefone' => $student->phone ?: '—',
        'Nascimento' => $student->birth_date?->format('d/m/Y') ?? '—',
        'Documento' => \App\Support\Document::format($student->document) ?: '—',
        'Sexo' => $genders[$student->gender?->value] ?? '—',
        'Início dos treinos' => $student->started_at?->format('d/m/Y') ?? '—',
        'Dias disponíveis' => $availableDays ? implode(', ', $availableDays) : '—',
    ];

    $physical = [
        'Altura' => $student->height ? $student->height.' cm' : '—',
        'Peso inicial' => $student->initial_weight ? number_format((float) $student->initial_weight, 1, ',', '.').' kg' : '—',
        'Peso atual' => $student->current_weight ? number_format((float) $student->current_weight, 1, ',', '.').' kg' : '—',
        'Peso meta' => $student->target_weight ? number_format((float) $student->target_weight, 1, ',', '.').' kg' : '—',
        'Nível' => $level ?? '—',
        'Experiência' => $student->training_experience ?: '—',
        'Objetivo' => $student->goal ?: '—',
    ];
@endphp

<div class="space-y-6">
    {{-- Cabeçalho --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 items-center gap-3">
            <a wire:navigate href="{{ route('students.index') }}" title="Voltar para a lista"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div class="min-w-0">
                <h1 class="truncate text-xl font-semibold tracking-tight text-gray-900">
                    {{ $student->name }}
                </h1>
                <p class="mt-0.5 text-sm text-gray-500">Alunos / Ficha do aluno</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a wire:navigate href="{{ route('students.tracking', $student) }}" class="btn btn-secondary btn-sm">
                <x-icon name="chart-bar" class="h-4 w-4" /> Acompanhamento
            </a>
            <a wire:navigate href="{{ route('students.messages', $student) }}" class="btn btn-secondary btn-sm">
                <x-icon name="envelope" class="h-4 w-4" /> Mensagens
            </a>
            <a href="{{ route('students.pdf', $student) }}" class="btn btn-secondary btn-sm">
                <x-icon name="arrow-down-tray" class="h-4 w-4" /> Baixar PDF
            </a>
            <a wire:navigate href="{{ route('students.edit', $student) }}" class="btn btn-primary btn-sm">
                <x-icon name="pencil" class="h-4 w-4" /> Editar
            </a>
        </div>
    </div>

    {{-- Identificação --}}
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="h-24 bg-gradient-to-r from-brand-900 via-brand-800 to-brand-600"></div>
        <div class="px-5 pb-5 sm:px-6">
            <div class="-mt-12 flex flex-wrap items-end gap-4">
                <div class="h-24 w-24 shrink-0 overflow-hidden rounded-2xl border-4 border-white bg-brand-100 shadow-md">
                    @if ($photoUrl)
                        <img src="{{ $photoUrl }}" alt="Foto de {{ $student->name }}" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-2xl font-bold text-brand-700">
                            {{ $initials }}
                        </span>
                    @endif
                </div>

                <div class="min-w-0 flex-1 pb-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold text-gray-900 sm:text-xl">{{ $student->name }}</h2>
                        <span class="badge {{ $student->active ? 'badge-success' : 'badge-secondary' }}">
                            {{ $student->active ? 'Ativo' : 'Inativo' }}
                        </span>
                        @if ($level)
                            <span class="badge badge-info">{{ $level }}</span>
                        @endif
                    </div>
                    <p class="mt-1 truncate text-sm text-gray-500">
                        {{ $student->email }}
                        @if ($student->phone)
                            <span class="text-gray-300">·</span> {{ $student->phone }}
                        @endif
                    </p>
                </div>

                @if ($student->started_at)
                    <div class="pb-1 text-sm text-gray-500">
                        Aluno desde <strong class="font-semibold text-gray-700">{{ $student->started_at->format('d/m/Y') }}</strong>
                    </div>
                @endif
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Idade</p>
                    <p class="mt-1 text-base font-semibold text-gray-900">{{ $age ? $age.' anos' : '—' }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Altura</p>
                    <p class="mt-1 text-base font-semibold text-gray-900">{{ $student->height ? $student->height.' cm' : '—' }}</p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Peso atual</p>
                    <p class="mt-1 text-base font-semibold text-gray-900">
                        {{ $student->current_weight ? number_format((float) $student->current_weight, 1, ',', '.').' kg' : '—' }}
                    </p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50/70 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Peso meta</p>
                    <p class="mt-1 text-base font-semibold text-gray-900">
                        {{ $student->target_weight ? number_format((float) $student->target_weight, 1, ',', '.').' kg' : '—' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Dados --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><x-icon name="identification" class="h-4 w-4 text-brand-600" />Dados pessoais</h3>
            </div>
            <div class="card-body">
                <dl class="text-sm">
                    @foreach ($personal as $label => $value)
                        <div class="flex items-start justify-between gap-4 border-b border-gray-100 py-2.5 last:border-0">
                            <dt class="shrink-0 text-gray-500">{{ $label }}</dt>
                            <dd class="text-right font-medium text-gray-900 break-words">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><x-icon name="scale" class="h-4 w-4 text-brand-600" />Dados físicos e objetivo</h3>
            </div>
            <div class="card-body">
                <dl class="text-sm">
                    @foreach ($physical as $label => $value)
                        <div class="flex items-start justify-between gap-4 border-b border-gray-100 py-2.5 last:border-0">
                            <dt class="shrink-0 text-gray-500">{{ $label }}</dt>
                            <dd class="text-right font-medium text-gray-900 break-words">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </div>

    {{-- Acesso no app + plano ativo --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><x-icon name="solid-qr-code" class="h-4 w-4 text-brand-600" />Acesso no app</h3>
                @if ($student->user_id)
                    <span class="badge badge-success">Conta ativa</span>
                @endif
            </div>
            <div class="card-body">
                @if ($student->user_id)
                    <p class="mb-0 text-sm text-gray-500">
                        Este aluno já possui conta de acesso. Login:
                        <strong class="font-semibold text-gray-700">{{ $student->email }}</strong>.
                    </p>
                @else
                    <p class="mb-3 text-sm text-gray-500">
                        Compartilhe o código com o aluno para ele criar a conta no app:
                    </p>
                    @if ($student->invite_code)
                        <div class="flex flex-wrap items-center gap-2">
                            <code class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-lg font-semibold tracking-widest text-gray-900">
                                {{ $student->invite_code }}
                            </code>
                            <button type="button" class="btn btn-secondary btn-sm"
                                x-data="{ copied: false }"
                                x-on:click="navigator.clipboard?.writeText('{{ $student->invite_code }}'); copied = true; setTimeout(() => copied = false, 1500)">
                                <span x-show="!copied">Copiar</span>
                                <span x-show="copied" x-cloak>Copiado!</span>
                            </button>
                        </div>
                    @else
                        <p class="mb-2 text-sm text-gray-500">Este aluno ainda não possui código de convite.</p>
                    @endif
                    <button type="button" class="btn btn-primary btn-sm mt-3" wire:click="regenerateInvite"
                        wire:confirm="Gerar um novo código invalida o atual. Continuar?">
                        <x-icon name="arrow-path" class="h-4 w-4" />
                        {{ $student->invite_code ? 'Gerar novo código' : 'Gerar código' }}
                    </button>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><x-icon name="clipboard-document-list" class="h-4 w-4 text-brand-600" />Plano ativo</h3>
            </div>
            <div class="card-body">
                @if ($activePlan)
                    <h5 class="mb-1 text-base font-semibold text-gray-900">{{ $activePlan->name }}</h5>
                    <p class="mb-3 text-sm text-gray-500">{{ $activePlan->goal ?: 'Sem meta definida.' }}</p>
                    <span class="badge badge-info">
                        {{ $activePlan->start_date?->format('d/m/Y') ?? '—' }}
                        @if ($activePlan->end_date)
                            → {{ $activePlan->end_date->format('d/m/Y') }}
                        @endif
                    </span>
                @else
                    <p class="mb-0 text-sm text-gray-500">Nenhum plano de treino ativo para este aluno.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Observações --}}
    @if ($student->observations)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><x-icon name="document-text" class="h-4 w-4 text-brand-600" />Observações</h3>
            </div>
            <div class="card-body">
                <p class="whitespace-pre-line text-sm text-gray-700">{{ $student->observations }}</p>
            </div>
        </div>
    @endif

    {{-- Últimos pagamentos --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><x-icon name="currency-dollar" class="h-4 w-4 text-brand-600" />Últimos pagamentos</h3>
        </div>
        <div class="card-body p-0">
            @if ($recentPayments->count())
                <div class="overflow-x-auto">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Descrição</th>
                                <th>Vencimento</th>
                                <th class="text-right">Valor</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentPayments as $payment)
                                <tr>
                                    <td>{{ $payment->description }}</td>
                                    <td>{{ $payment->due_date?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="text-right">R$ {{ number_format((float) $payment->amount, 2, ',', '.') }}</td>
                                    <td class="text-center">
                                        @php
                                            $badgeClass = match ($payment->status->value) {
                                                'paid' => 'badge-success',
                                                'overdue' => 'badge-danger',
                                                'cancelled' => 'badge-secondary',
                                                default => 'badge-warning',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">{{ $paymentStatus[$payment->status->value] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="p-4 text-sm text-gray-500">Nenhum pagamento registrado.</p>
            @endif
        </div>
    </div>

    {{-- Observações internas do treinador (componente aninhado) --}}
    @livewire('dashboard.students.student-notes', ['student' => $student], key('student-notes'))
</div>
