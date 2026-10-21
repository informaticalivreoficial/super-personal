<div>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('admin') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-brand-700">
                <x-icon name="arrow-left" class="h-4 w-4" /> Painel
            </a>
            <h1 class="mt-2 flex items-center gap-2 text-2xl font-bold text-gray-900">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-100 text-brand-700">
                    <x-icon name="chart-bar" class="h-5 w-5" />
                </span>
                Relatório de Aderência
            </h1>
            <p class="mt-1 text-sm text-gray-600">
                O que foi prescrito × o que o aluno realmente executou. Competência pela data agendada da sessão.
            </p>
        </div>

        <a href="{{ $exportUrl }}" class="btn btn-primary">
            <x-icon name="arrow-down-tray" class="h-4 w-4" /> Exportar CSV
        </a>
    </div>

    {{-- Filtros --}}
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex flex-wrap gap-2">
                    @foreach ($periods as $key => $label)
                        <button type="button" wire:click="setPeriod('{{ $key }}')"
                            @class([
                                'rounded-full border px-3 py-1.5 text-sm font-medium transition',
                                $period === $key
                                    ? 'border-brand-600 bg-brand-600 text-white'
                                    : 'border-gray-300 bg-white text-gray-700 hover:border-brand-400 hover:text-brand-700',
                            ])>
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="ml-auto flex flex-wrap items-end gap-3">
                    <div>
                        <label class="form-label" for="adherence_from">De</label>
                        <input type="date" id="adherence_from" class="form-control" wire:model.live="from">
                    </div>
                    <div>
                        <label class="form-label" for="adherence_to">Até</label>
                        <input type="date" id="adherence_to" class="form-control" wire:model.live="to">
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-end gap-3 border-t border-gray-100 pt-4">
                <div class="min-w-[14rem]">
                    <label class="form-label" for="adherence_student">Aluno</label>
                    <select id="adherence_student" class="form-control" wire:model.live="filterStudentId">
                        <option value="">Todos os alunos</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[12rem]">
                    <label class="form-label" for="adherence_sport">Modalidade</label>
                    <select id="adherence_sport" class="form-control" wire:model.live="filterSportId">
                        <option value="">Todas as modalidades</option>
                        @foreach ($sports as $sport)
                            <option value="{{ $sport->id }}">{{ $sport->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="button" class="btn btn-secondary" wire:click="clearFilters">
                    Limpar filtros
                </button>
                <span class="ml-auto text-sm text-gray-500">
                    Período: <strong>{{ \Illuminate\Support\Carbon::parse($filters['from'])->format('d/m/Y') }}</strong>
                    a <strong>{{ \Illuminate\Support\Carbon::parse($filters['to'])->format('d/m/Y') }}</strong>
                </span>
            </div>
        </div>
    </div>

    {{-- Indicadores --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card"><div class="card-body">
            <p class="text-sm font-medium text-gray-500">Aderência</p>
            <p class="mt-2 text-3xl font-bold {{ $summary['adherence'] === null ? 'text-gray-400' : ($summary['adherence'] >= 70 ? 'text-emerald-600' : ($summary['adherence'] >= 40 ? 'text-amber-600' : 'text-red-600')) }}">
                {{ $summary['adherence'] === null ? '—' : $summary['adherence'].'%' }}
            </p>
            <p class="mt-1 text-xs text-gray-500">{{ $summary['due'] }} sessão(ões) já vencida(s)</p>
        </div></div>

        <div class="card"><div class="card-body">
            <p class="text-sm font-medium text-gray-500">Sessões no período</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ $summary['total'] }}</p>
            <p class="mt-1 text-xs text-gray-500">
                {{ $summary['completed'] }} concluída(s) · {{ $summary['planned'] }} a vir
            </p>
        </div></div>

        <div class="card"><div class="card-body">
            <p class="text-sm font-medium text-gray-500">Não realizadas</p>
            <p class="mt-2 text-3xl font-bold text-red-600">{{ $summary['missed'] }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ $summary['skipped'] }} pulada(s) · {{ $summary['cancelled'] }} cancelada(s)</p>
        </div></div>

        <div class="card"><div class="card-body">
            <p class="text-sm font-medium text-gray-500">Volume concluído</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">
                {{ $summary['distance'] > 0 ? number_format($summary['distance'] / 1000, 1, ',', '.').' km' : '—' }}
            </p>
            <p class="mt-1 text-xs text-gray-500">
                @php
                    $minutes = intdiv($summary['duration'], 60);
                    $hours = intdiv($minutes, 60);
                @endphp
                {{ $hours > 0 ? $hours.'h'.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT) : $minutes.' min' }} de treino
            </p>
        </div></div>
    </div>

    @if ($summary['total'] === 0)
        <div class="card"><div class="card-body py-12 text-center">
            <x-icon name="chart-bar" class="mx-auto h-10 w-10 text-gray-300" />
            <p class="mt-3 text-base font-medium text-gray-900">Nenhuma sessão no período</p>
            <p class="mt-1 text-sm text-gray-500">
                Ajuste o período ou os filtros — só entram sessões de planos publicados.
            </p>
        </div></div>
    @else
        <div class="grid gap-6 xl:grid-cols-2">
            {{-- Ranking por aluno --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="text-base font-semibold text-gray-900">Aderência por aluno</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Aluno</th>
                                    <th class="text-right">Sessões</th>
                                    <th class="text-right">Concluídas</th>
                                    <th class="text-right">Aderência</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($byStudent as $row)
                                    <tr>
                                        <td>
                                            @if ($row['student'])
                                                <a href="{{ route('students.show', $row['student']) }}" class="font-medium text-brand-700 hover:underline">
                                                    {{ $row['student']->name }}
                                                </a>
                                            @else
                                                —
                                            @endif
                                            @if ($row['missed'] > 0)
                                                <span class="block text-xs text-red-600">{{ $row['missed'] }} não realizada(s)</span>
                                            @endif
                                        </td>
                                        <td class="text-right">{{ $row['total'] }}</td>
                                        <td class="text-right">{{ $row['completed'] }}</td>
                                        <td class="text-right font-semibold {{ $row['adherence'] === null ? 'text-gray-400' : ($row['adherence'] >= 70 ? 'text-emerald-600' : ($row['adherence'] >= 40 ? 'text-amber-600' : 'text-red-600')) }}">
                                            {{ $row['adherence'] === null ? '—' : $row['adherence'].'%' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Por modalidade --}}
            <div class="card">
                <div class="card-header">
                    <h2 class="text-base font-semibold text-gray-900">Aderência por modalidade</h2>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Modalidade</th>
                                    <th class="text-right">Sessões</th>
                                    <th class="text-right">Concluídas</th>
                                    <th class="text-right">Aderência</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bySport as $row)
                                    <tr>
                                        <td class="font-medium text-gray-900">{{ $row['sport']?->name ?? '—' }}</td>
                                        <td class="text-right">{{ $row['total'] }}</td>
                                        <td class="text-right">{{ $row['completed'] }}</td>
                                        <td class="text-right font-semibold {{ $row['adherence'] === null ? 'text-gray-400' : ($row['adherence'] >= 70 ? 'text-emerald-600' : ($row['adherence'] >= 40 ? 'text-amber-600' : 'text-red-600')) }}">
                                            {{ $row['adherence'] === null ? '—' : $row['adherence'].'%' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sessões --}}
        <div class="card mt-6">
            <div class="card-header">
                <h2 class="text-base font-semibold text-gray-900">Sessões do período</h2>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Aluno</th>
                                <th>Treino</th>
                                <th>Modalidade</th>
                                <th>Situação</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sessions as $session)
                                @php
                                    $tone = match ($session->status) {
                                        \App\Enums\TrainingSessionStatus::COMPLETED => 'bg-emerald-100 text-emerald-800',
                                        \App\Enums\TrainingSessionStatus::SKIPPED => 'bg-amber-100 text-amber-800',
                                        \App\Enums\TrainingSessionStatus::CANCELLED => 'bg-gray-100 text-gray-700',
                                        default => 'bg-sky-100 text-sky-800',
                                    };
                                @endphp
                                <tr>
                                    <td class="whitespace-nowrap">{{ $session->scheduled_date?->format('d/m/Y') }}</td>
                                    <td>
                                        @if ($session->student)
                                            <a href="{{ route('students.show', $session->student) }}" class="text-brand-700 hover:underline">
                                                {{ $session->student->name }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="max-w-[18rem]">
                                        <span class="block truncate font-medium text-gray-900">{{ $session->title }}</span>
                                    </td>
                                    <td>{{ $session->sport?->name ?? '—' }}</td>
                                    <td>
                                        <span class="badge {{ $tone }}">{{ $statusLabels[$session->status->value] ?? $session->status->value }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 px-4 py-3">
                    {{ $sessions->links() }}
                </div>
            </div>
        </div>
    @endif
</div>
