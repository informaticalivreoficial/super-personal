<div>
    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a wire:navigate href="{{ route('students.index') }}" title="Voltar"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition hover:bg-gray-50 hover:text-gray-700">
                <x-icon name="arrow-left" class="h-5 w-5" />
            </a>
            <div>
                <h1 class="flex items-center gap-2 text-xl font-semibold tracking-tight text-gray-900">
                    <x-icon name="identification" class="h-6 w-6 text-teal-600" />
                    {{ $student->name }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">Alunos / Detalhes</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a wire:navigate href="{{ route('students.tracking', $student) }}" class="btn btn-secondary btn-sm">
                <x-icon name="chart-bar" class="h-4 w-4" /> Acompanhamento
            </a>
            <a wire:navigate href="{{ route('students.edit', $student) }}" class="btn btn-primary btn-sm">
                <x-icon name="pencil" class="h-4 w-4" /> Editar
            </a>
            <a wire:navigate href="{{ route('students.index') }}" class="btn btn-secondary btn-sm">
                <x-icon name="arrow-left" class="h-4 w-4" /> Voltar
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-teal card-outline">
                <div class="card-header">
                    <h3 class="card-title"><x-icon name="user" class="h-4 w-4 text-teal-600" />Dados cadastrais</h3>
                    <div class="card-tools">
                        <span class="badge {{ $student->active ? 'badge-success' : 'badge-secondary' }}">
                            {{ $student->active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-5 text-muted">E-mail</dt>
                                <dd class="col-sm-7">{{ $student->email }}</dd>

                                <dt class="col-sm-5 text-muted">Telefone</dt>
                                <dd class="col-sm-7">{{ $student->phone ?: '—' }}</dd>

                                <dt class="col-sm-5 text-muted">Nascimento</dt>
                                <dd class="col-sm-7">{{ $student->birth_date?->format('d/m/Y') ?? '—' }}</dd>

                                <dt class="col-sm-5 text-muted">Documento</dt>
                                <dd class="col-sm-7">{{ $student->document ?: '—' }}</dd>

                                <dt class="col-sm-5 text-muted">Sexo</dt>
                                <dd class="col-sm-7">{{ $genders[$student->gender?->value] ?? '—' }}</dd>

                                <dt class="col-sm-5 text-muted">Início dos treinos</dt>
                                <dd class="col-sm-7">{{ $student->started_at?->format('d/m/Y') ?? '—' }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-5 text-muted">Altura</dt>
                                <dd class="col-sm-7">{{ $student->height ? $student->height . ' cm' : '—' }}</dd>

                                <dt class="col-sm-5 text-muted">Peso inicial</dt>
                                <dd class="col-sm-7">{{ $student->initial_weight ? number_format((float) $student->initial_weight, 1, ',', '.') . ' kg' : '—' }}</dd>

                                <dt class="col-sm-5 text-muted">Peso atual</dt>
                                <dd class="col-sm-7">{{ $student->current_weight ? number_format((float) $student->current_weight, 1, ',', '.') . ' kg' : '—' }}</dd>

                                <dt class="col-sm-5 text-muted">Peso meta</dt>
                                <dd class="col-sm-7">{{ $student->target_weight ? number_format((float) $student->target_weight, 1, ',', '.') . ' kg' : '—' }}</dd>

                                <dt class="col-sm-5 text-muted">Nível</dt>
                                <dd class="col-sm-7">{{ $levels[$student->fitness_level?->value] ?? '—' }}</dd>

                                <dt class="col-sm-5 text-muted">Objetivo</dt>
                                <dd class="col-sm-7">{{ $student->goal ?: '—' }}</dd>
                            </dl>
                        </div>
                    </div>

                    @if ($student->observations)
                        <hr>
                        <h6 class="text-muted">Observações</h6>
                        <p class="mb-0">{{ $student->observations }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><x-icon name="clipboard-document-list" class="h-4 w-4 text-teal-600" />Plano ativo</h3>
                </div>
                <div class="card-body">
                    @if ($activePlan)
                        <h5 class="mb-1">{{ $activePlan->name }}</h5>
                        <p class="text-muted mb-2">{{ $activePlan->goal ?: 'Sem meta definida.' }}</p>
                        <span class="badge badge-info">
                            {{ $activePlan->start_date?->format('d/m/Y') ?? '—' }}
                            @if ($activePlan->end_date)
                                → {{ $activePlan->end_date->format('d/m/Y') }}
                            @endif
                        </span>
                    @else
                        <p class="text-muted mb-0">Nenhum plano de treino ativo para este aluno.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><x-icon name="currency-dollar" class="h-4 w-4 text-teal-600" />Últimos pagamentos</h3>
                </div>
                <div class="card-body p-0">
                    @if ($recentPayments->count())
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
            @else
                <p class="text-muted p-3 mb-0">Nenhum pagamento registrado.</p>
            @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            @livewire('dashboard.students.student-notes', ['student' => $student], key('student-notes'))
        </div>

        <div class="col-12 col-md-6">
            @livewire('dashboard.students.student-messages', ['student' => $student], key('student-messages'))
        </div>
    </div>
</div>
