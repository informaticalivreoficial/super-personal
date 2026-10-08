<div>
    @php
        // duração em segundos → "1h05" / "45min" / "30s"
        $durationLabel = function ($seconds) {
            if ($seconds === null || $seconds === '') {
                return '—';
            }
            $seconds = (int) $seconds;
            if ($seconds >= 3600) {
                return intdiv($seconds, 3600).'h'.str_pad((string) intdiv($seconds % 3600, 60), 2, '0', STR_PAD_LEFT);
            }
            if ($seconds >= 60) {
                return intdiv($seconds, 60).'min';
            }
            return $seconds.'s';
        };

        // distância em metros → "10,50 km" / "800 m"
        $distanceLabel = function ($meters) {
            if ($meters === null || $meters === '') {
                return '—';
            }
            $meters = (float) $meters;
            return $meters >= 1000
                ? number_format($meters / 1000, 2, ',', '.').' km'
                : number_format($meters, 0, ',', '.').' m';
        };
    @endphp

    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('students.show', $student) }}" title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="chart-bar" class="h-6 w-6 text-teal-600" />
                    {{ $student->name }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">Alunos / Acompanhamento</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a wire:navigate href="{{ route('students.show', $student) }}" class="btn btn-secondary btn-sm">
                <x-icon name="identification" class="h-4 w-4" /> Ficha do aluno
            </a>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-5">
            <div class="flex items-center gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
                    <x-icon name="check" class="h-6 w-6" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-500">Concluídas (mês)</p>
                    <p class="text-2xl font-semibold tracking-tight text-gray-900">{{ $completedThisMonth }}</p>
                    <p class="text-xs text-gray-400">Total: {{ $totalExecutions }} execuções</p>
                </div>
            </div>
        </div>

        <div class="card p-5">
            <div class="flex items-center gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                    <x-icon name="clipboard-document-list" class="h-6 w-6" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-500">Aderência ao plano</p>
                    <p class="text-2xl font-semibold tracking-tight text-gray-900">
                        {{ $adherence !== null ? $adherence.'%' : '—' }}
                    </p>
                    <p class="text-xs text-gray-400">
                        @if ($activePlan)
                            {{ $planCompleted }}/{{ $planSessions }} concluídos · {{ $planSkipped }} pulados
                        @else
                            Sem plano ativo
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="card p-5">
            <div class="flex items-center gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <x-icon name="fire" class="h-6 w-6" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-500">Última execução</p>
                    @php
                        $lastAt = $lastExecution?->completed_at ?? $lastExecution?->started_at;
                    @endphp
                    <p class="text-2xl font-semibold tracking-tight text-gray-900">
                        {{ $lastAt?->format('d/m/Y') ?? '—' }}
                    </p>
                    <p class="truncate text-xs text-gray-400">
                        {{ $lastExecution?->session?->title ?? 'Nenhuma execução registrada' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="card p-5">
            <div class="flex items-center gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <x-icon name="clipboard" class="h-6 w-6" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-500">Avaliações físicas</p>
                    <p class="text-2xl font-semibold tracking-tight text-gray-900">{{ $progress->total() }}</p>
                    <p class="text-xs text-gray-400">
                        Última: {{ $progress->first()?->recorded_at?->format('d/m/Y') ?? '—' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Avaliações físicas --}}
    <div class="mb-6 card">
        <div class="card-header">
            <h3 class="card-title">
                <x-icon name="clipboard" class="h-4 w-4 text-teal-600" />Histórico de avaliações
            </h3>
            <div class="card-tools">
                <button type="button" wire:click="toggleForm" class="btn btn-primary btn-sm">
                    <x-icon name="{{ $showForm ? 'x-mark' : 'plus' }}" class="h-4 w-4" />
                    {{ $showForm ? 'Cancelar' : 'Nova avaliação' }}
                </button>
            </div>
        </div>

        @if ($showForm)
            <div class="border-b border-gray-100 bg-gray-50 p-5">
                <form wire:submit="saveProgress">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label for="recorded_at" class="mb-1 block text-sm font-medium text-gray-700">Data *</label>
                            <input type="date" id="recorded_at" wire:model="recorded_at"
                                class="form-control @error('recorded_at') is-invalid @enderror">
                            @error('recorded_at')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="weight" class="mb-1 block text-sm font-medium text-gray-700">Peso (kg)</label>
                            <input type="number" step="0.1" id="weight" wire:model="weight"
                                class="form-control @error('weight') is-invalid @enderror">
                            @error('weight')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="body_fat" class="mb-1 block text-sm font-medium text-gray-700">Gordura corporal (%)</label>
                            <input type="number" step="0.1" id="body_fat" wire:model="body_fat"
                                class="form-control @error('body_fat') is-invalid @enderror">
                            @error('body_fat')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="resting_heart_rate" class="mb-1 block text-sm font-medium text-gray-700">FC repouso (bpm)</label>
                            <input type="number" id="resting_heart_rate" wire:model="resting_heart_rate"
                                class="form-control @error('resting_heart_rate') is-invalid @enderror">
                            @error('resting_heart_rate')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="max_heart_rate" class="mb-1 block text-sm font-medium text-gray-700">FC máx (bpm)</label>
                            <input type="number" id="max_heart_rate" wire:model="max_heart_rate"
                                class="form-control @error('max_heart_rate') is-invalid @enderror">
                            @error('max_heart_rate')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="ftp" class="mb-1 block text-sm font-medium text-gray-700">FTP (W)</label>
                            <input type="number" id="ftp" wire:model="ftp"
                                class="form-control @error('ftp') is-invalid @enderror">
                            @error('ftp')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="running_pace" class="mb-1 block text-sm font-medium text-gray-700">Ritmo corrida</label>
                            <input type="text" id="running_pace" wire:model="running_pace" placeholder="ex.: 5:30/km"
                                class="form-control @error('running_pace') is-invalid @enderror">
                            @error('running_pace')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="swimming_pace" class="mb-1 block text-sm font-medium text-gray-700">Ritmo natação</label>
                            <input type="text" id="swimming_pace" wire:model="swimming_pace" placeholder="ex.: 1:45/100m"
                                class="form-control @error('swimming_pace') is-invalid @enderror">
                            @error('swimming_pace')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="sm:col-span-2 lg:col-span-4">
                            <label for="notes" class="mb-1 block text-sm font-medium text-gray-700">Observações</label>
                            <textarea id="notes" wire:model="notes" rows="2"
                                class="form-control @error('notes') is-invalid @enderror"></textarea>
                            @error('notes')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-2">
                        <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="saveProgress">
                                <x-icon name="check" class="h-4 w-4" /> Salvar avaliação
                            </span>
                            <span wire:loading wire:target="saveProgress">Salvando...</span>
                        </button>
                        <button type="button" wire:click="toggleForm" class="btn btn-secondary btn-sm">Fechar</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="card-body p-0">
            @if ($progress->count())
                <div class="overflow-x-auto">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th class="text-right">Peso</th>
                                <th class="text-right">Gordura</th>
                                <th class="text-right">FC rep.</th>
                                <th class="text-right">FTP</th>
                                <th>Ritmos</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($progress as $record)
                                <tr wire:key="progress-{{ $record->id }}">
                                    <td>{{ $record->recorded_at?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="text-right">{{ $record->weight ? number_format((float) $record->weight, 1, ',', '.').' kg' : '—' }}</td>
                                    <td class="text-right">{{ $record->body_fat ? number_format((float) $record->body_fat, 1, ',', '.').' %' : '—' }}</td>
                                    <td class="text-right">{{ $record->resting_heart_rate ?: '—' }}</td>
                                    <td class="text-right">{{ $record->ftp ? $record->ftp.' W' : '—' }}</td>
                                    <td>
                                        @php
                                            $paces = collect([
                                                $record->running_pace ? 'Corr.: '.$record->running_pace : null,
                                                $record->swimming_pace ? 'Nat.: '.$record->swimming_pace : null,
                                            ])->filter()->implode(' · ');
                                        @endphp
                                        {{ $paces ?: '—' }}
                                    </td>
                                    <td class="max-w-xs truncate">{{ $record->notes ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">
                    {{ $progress->links() }}
                </div>
            @else
                <p class="p-4 text-sm text-gray-500">Nenhuma avaliação registrada para este aluno.</p>
            @endif
        </div>
    </div>

    {{-- Execuções --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <x-icon name="fire" class="h-4 w-4 text-teal-600" />Execuções dos treinos
            </h3>
            <div class="card-tools">
                <span class="text-sm text-gray-500">{{ $executions->total() }} registro(s)</span>
            </div>
        </div>
        <div class="card-body p-0">
            @if ($executions->count())
                <div class="overflow-x-auto">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Sessão</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Duração</th>
                                <th class="text-right">Distância</th>
                                <th class="text-center">Esforço</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($executions as $execution)
                                <tr wire:key="execution-{{ $execution->id }}">
                                    <td>{{ ($execution->completed_at ?? $execution->started_at)?->format('d/m/Y') ?? '—' }}</td>
                                    <td>
                                        <span class="font-medium">{{ $execution->session?->title ?? '—' }}</span>
                                        @if ($execution->session?->sport)
                                            <span class="ml-1 text-xs text-gray-400">{{ $execution->session->sport->name }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $statusBadge = match ($execution->status?->value) {
                                                'completed' => 'badge-success',
                                                'started' => 'badge-warning',
                                                default => 'badge-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $statusBadge }}">
                                            {{ $executionStatus[$execution->status?->value] ?? '—' }}
                                        </span>
                                    </td>
                                    <td class="text-right">{{ $durationLabel($execution->duration) }}</td>
                                    <td class="text-right">{{ $distanceLabel($execution->distance) }}</td>
                                    <td class="text-center">{{ $execution->perceived_effort ?: '—' }}</td>
                                    <td class="max-w-xs truncate">{{ $execution->notes ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3">
                    {{ $executions->links() }}
                </div>
            @else
                <p class="p-4 text-sm text-gray-500">Nenhuma execução registrada. Os treinos executados no app do aluno aparecem aqui.</p>
            @endif
        </div>
    </div>
</div>
