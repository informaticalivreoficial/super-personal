<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                <x-icon name="calendar-days" class="h-6 w-6 text-brand-600" />
                Agenda semanal
            </h1>
            <p class="mt-1 text-sm text-gray-500">Todos os treinos dos seus alunos na semana</p>
        </div>

        {{-- Navegação entre semanas --}}
        <div class="flex items-center gap-2">
            <button type="button" wire:click="previousWeek" class="btn btn-secondary btn-sm" title="Semana anterior">
                <x-icon name="chevron-left" class="h-4 w-4" />
            </button>
            <button type="button" wire:click="currentWeek"
                class="{{ $isCurrentWeek ? 'btn btn-secondary btn-sm opacity-60' : 'btn btn-primary btn-sm' }}">
                <x-icon name="calendar" class="h-4 w-4" />
                Hoje
            </button>
            <button type="button" wire:click="nextWeek" class="btn btn-secondary btn-sm" title="Próxima semana">
                <x-icon name="chevron-right" class="h-4 w-4" />
            </button>
        </div>
    </div>

    {{-- Semana exibida --}}
    <div class="mb-6 flex flex-wrap items-center gap-2">
        <h2 class="text-lg font-semibold tracking-tight text-gray-900">{{ $weekLabel }}</h2>
        @if ($isCurrentWeek)
            <span class="badge badge-info">Semana atual</span>
        @endif
    </div>

    @if ($totals['sessions'] > 0)
        {{-- KPIs da semana --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div class="card p-5">
                <div class="flex items-center gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                        <x-icon name="clipboard-document-list" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-500">Treinos na semana</p>
                        <p class="text-2xl font-semibold tracking-tight text-gray-900">{{ $totals['sessions'] }}</p>
                        <p class="text-xs text-gray-400">{{ $totals['completed'] }} concluído(s)</p>
                    </div>
                </div>
            </div>

            <div class="card p-5">
                <div class="flex items-center gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                        <x-icon name="academic-cap" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-500">Alunos</p>
                        <p class="text-2xl font-semibold tracking-tight text-gray-900">{{ $totals['students'] }}</p>
                        <p class="text-xs text-gray-400">com treino na semana</p>
                    </div>
                </div>
            </div>

            <div class="card p-5">
                <div class="flex items-center gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-green-50 text-green-600">
                        <x-icon name="check-badge" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-500">Concluídos</p>
                        <p class="text-2xl font-semibold tracking-tight text-gray-900">{{ $totals['completed'] }}</p>
                        <p class="text-xs text-gray-400">de {{ $totals['sessions'] }} treino(s)</p>
                    </div>
                </div>
            </div>

            <div class="card p-5">
                <div class="flex items-center gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <x-icon name="clock" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-500">Planejados</p>
                        <p class="text-2xl font-semibold tracking-tight text-gray-900">{{ $totals['planned'] }}</p>
                        @if ($totals['skipped'] > 0 || $totals['cancelled'] > 0)
                            <p class="text-xs text-gray-400">
                                {{ $totals['skipped'] }} pulado(s) · {{ $totals['cancelled'] }} cancelado(s)
                            </p>
                        @else
                            <p class="text-xs text-gray-400">ainda por treinar</p>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card p-5">
                <div class="flex items-center gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <x-icon name="chart-bar" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-500">Duração total</p>
                        @php
                            $totalMinutes = intdiv($totals['duration_seconds'], 60);
                            $totalDuration = $totalMinutes >= 60
                                ? sprintf('%dh%02d', intdiv($totalMinutes, 60), $totalMinutes % 60)
                                : $totalMinutes.' min';
                        @endphp
                        <p class="text-2xl font-semibold tracking-tight text-gray-900">{{ $totalDuration }}</p>
                        @if ($totals['distance_meters'] > 0)
                            <p class="text-xs text-gray-400">
                                {{ rtrim(rtrim(number_format($totals['distance_meters'] / 1000, 2, ',', '.'), '0'), ',') }} km
                            </p>
                        @else
                            <p class="text-xs text-gray-400">estimada</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Filtros --}}
    <div class="card mb-6">
        <div class="card-body flex flex-wrap items-end gap-2">
            <div class="min-w-[200px] flex-1">
                <label class="form-label form-label-sm">Aluno</label>
                <select class="form-control form-control-sm" wire:model.live="filterStudent">
                    <option value="">Todos os alunos</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}">{{ $student->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[160px] flex-1">
                <label class="form-label form-label-sm">Modalidade</label>
                <select class="form-control form-control-sm" wire:model.live="filterSport">
                    <option value="">Todas as modalidades</option>
                    @foreach ($sports as $sport)
                        <option value="{{ $sport->id }}">{{ $sport->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[150px] flex-1">
                <label class="form-label form-label-sm">Status</label>
                <select class="form-control form-control-sm" wire:model.live="filterStatus">
                    <option value="">Todos os status</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if ($hasFilters)
                <button type="button" class="btn btn-secondary btn-sm" wire:click="clearFilters">
                    <x-icon name="x-mark" class="h-4 w-4" />
                    Limpar filtros
                </button>
            @endif
        </div>
    </div>

    @if ($totals['sessions'] === 0)
        {{-- Sem treinos no recorte --}}
        <div class="card">
            <div class="card-body py-12 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
                    <x-icon name="calendar-days" class="h-6 w-6" />
                </span>
                @if ($hasFilters)
                    <p class="mt-3 text-sm font-medium text-gray-900">Nenhum treino encontrado com esses filtros.</p>
                    <button type="button" class="mt-2 text-sm font-semibold text-brand-600 hover:text-brand-700"
                        wire:click="clearFilters">
                        Limpar filtros
                    </button>
                @else
                    <p class="mt-3 text-sm font-medium text-gray-900">Nenhum treino agendado nesta semana.</p>
                    <p class="mt-1 text-sm text-gray-500">
                        Monte um plano de treino para os seus alunos e as sessões aparecem aqui.
                    </p>
                    <a wire:navigate href="{{ route('plans.index') }}"
                        class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-brand-600 hover:text-brand-700">
                        <x-icon name="clipboard-document-list" class="h-4 w-4" />
                        Ir para os planos de treino
                    </a>
                @endif
            </div>
        </div>
    @else
        {{-- Semana: um card por dia --}}
        <div class="space-y-4">
            @foreach ($days as $day)
                @php
                    $dayLabel = $day['is_today']
                        ? 'Hoje'
                        : ($day['date']->isTomorrow() ? 'Amanhã' : ($day['date']->isYesterday() ? 'Ontem' : ucfirst($day['date']->translatedFormat('l'))));
                @endphp
                <div class="card" wire:key="agenda-day-{{ $day['date']->toDateString() }}">
                    <div class="card-header flex items-center justify-between {{ $day['is_today'] ? 'bg-brand-50' : '' }}">
                        <div class="flex items-center gap-3">
                            <span @class([
                                'flex h-10 w-10 flex-col items-center justify-center rounded-lg leading-none',
                                'bg-brand-600 text-white' => $day['is_today'],
                                'border border-gray-200 bg-white text-gray-700' => ! $day['is_today'],
                            ])>
                                <span class="text-[10px] font-medium uppercase">{{ $day['date']->translatedFormat('M') }}</span>
                                <span class="text-sm font-semibold">{{ $day['date']->format('d') }}</span>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ $dayLabel }}
                                    @if ($day['is_today'])
                                        <span class="badge badge-info">hoje</span>
                                    @endif
                                </p>
                                <p class="text-xs text-gray-400">{{ $day['date']->translatedFormat('d \d\e F \d\e Y') }}</p>
                            </div>
                        </div>
                        <span class="badge {{ $day['sessions']->isNotEmpty() ? 'badge-secondary' : 'badge-secondary opacity-60' }}">
                            {{ $day['sessions']->count() }} treino(s)
                        </span>
                    </div>

                    <div class="card-body">
                        @if ($day['sessions']->isNotEmpty())
                            <div class="space-y-2">
                                @foreach ($day['sessions'] as $session)
                                    @php
                                        $seconds = (int) $session->estimated_duration;
                                        $minutes = intdiv($seconds, 60);
                                        $durationLabel = $minutes >= 60
                                            ? sprintf('%dh%02d', intdiv($minutes, 60), $minutes % 60)
                                            : $minutes.' min';

                                        $distance = (int) $session->distance;
                                        $distanceLabel = $distance >= 1000
                                            ? rtrim(rtrim(number_format($distance / 1000, 2, ',', '.'), '0'), ',').' km'
                                            : ($distance > 0 ? $distance.' m' : null);

                                        $status = $session->status?->value;
                                        $statusBadge = match ($status) {
                                            'completed' => 'badge-success',
                                            'skipped' => 'badge-secondary',
                                            'cancelled' => 'badge-danger',
                                            default => 'badge-warning',
                                        };
                                        $planId = $session->week?->training_plan_id;
                                    @endphp
                                    <div wire:key="agenda-session-{{ $session->id }}"
                                        class="flex flex-wrap items-center gap-3 rounded-lg border border-gray-100 p-3 transition hover:border-brand-200 hover:bg-brand-50/40">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600">
                                            <x-icon name="fire" class="h-5 w-5" />
                                        </span>

                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-gray-900">{{ $session->title }}</p>
                                            <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500">
                                                <a wire:navigate href="{{ route('students.show', $session->student_id) }}"
                                                    class="font-medium text-gray-700 hover:text-brand-600">
                                                    {{ $session->student?->name ?? '—' }}
                                                </a>
                                                @if ($session->sport)
                                                    <span class="text-gray-300">•</span>
                                                    <span>{{ $session->sport->name }}</span>
                                                @endif
                                                @if ($session->estimated_duration)
                                                    <span class="text-gray-300">•</span>
                                                    <span>{{ $durationLabel }}</span>
                                                @endif
                                                @if ($distanceLabel)
                                                    <span class="text-gray-300">•</span>
                                                    <span>{{ $distanceLabel }}</span>
                                                @endif
                                                @if ($session->intensity)
                                                    <span class="text-gray-300">•</span>
                                                    <span>{{ $session->intensity }}</span>
                                                @endif
                                            </p>
                                        </div>

                                        <span class="badge {{ $statusBadge }}">{{ $statuses[$status] ?? '—' }}</span>

                                        @if ($planId)
                                            <a wire:navigate href="{{ route('plans.show', $planId) }}"
                                                class="btn btn-xs btn-secondary" title="Abrir o plano do aluno">
                                                <x-icon name="pencil" class="h-4 w-4" />
                                            </a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="py-2 text-sm text-gray-400">Sem treinos neste dia.</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
