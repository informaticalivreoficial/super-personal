<div>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-id-card mr-2"></i>{{ $student->name }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a wire:navigate href="{{ route('admin') }}">Painel</a></li>
                        <li class="breadcrumb-item"><a wire:navigate href="{{ route('students.index') }}">Alunos</a></li>
                        <li class="breadcrumb-item active">Detalhes</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-teal card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user mr-2"></i>Dados cadastrais</h3>
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
                <div class="card-footer">
                    <a wire:navigate href="{{ route('students.edit', $student) }}" class="btn btn-teal btn-sm">
                        <i class="fas fa-pen mr-1"></i> Editar
                    </a>
                    <a wire:navigate href="{{ route('students.index') }}" class="btn btn-default btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Voltar
                    </a>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clipboard-list mr-2"></i>Plano ativo</h3>
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
                    <h3 class="card-title"><i class="fas fa-dollar-sign mr-2"></i>Últimos pagamentos</h3>
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
    </div>
</div>
