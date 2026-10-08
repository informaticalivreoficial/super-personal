<div>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-clipboard-list mr-2"></i>{{ $plan->name }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a wire:navigate href="{{ route('admin') }}">Painel</a></li>
                        <li class="breadcrumb-item"><a wire:navigate href="{{ route('plans.index') }}">Planos</a></li>
                        <li class="breadcrumb-item active">Detalhes</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    {{-- Cabeçalho do plano --}}
    <div class="card card-teal card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Informações</h3>
            <div class="card-tools">
                <span class="badge {{ $plan->status->value === 'active' ? 'badge-success' : 'badge-secondary' }}">
                    {{ $planStatus[$plan->status->value] }}
                </span>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Aluno</dt>
                        <dd class="col-sm-8">
                            @if ($plan->student)
                                <a wire:navigate href="{{ route('students.show', $plan->student) }}">
                                    {{ $plan->student->name }}
                                </a>
                            @else
                                —
                            @endif
                        </dd>

                        <dt class="col-sm-4 text-muted">Meta</dt>
                        <dd class="col-sm-8">{{ $plan->goal ?: '—' }}</dd>

                        <dt class="col-sm-4 text-muted">Descrição</dt>
                        <dd class="col-sm-8">{{ $plan->description ?: '—' }}</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Período</dt>
                        <dd class="col-sm-8">
                            {{ $plan->start_date?->format('d/m/Y') ?? '—' }}
                            @if ($plan->end_date) → {{ $plan->end_date->format('d/m/Y') }} @endif
                        </dd>

                        <dt class="col-sm-4 text-muted">Semanas</dt>
                        <dd class="col-sm-8">{{ $plan->weeks->count() }}</dd>

                        <dt class="col-sm-4 text-muted">Sessões</dt>
                        <dd class="col-sm-8">{{ $plan->weeks->sum(fn ($w) => $w->sessions->count()) }}</dd>
                    </dl>
                </div>
            </div>

            @if ($plan->notes)
                <hr>
                <h6 class="text-muted">Observações</h6>
                <p class="mb-0">{{ $plan->notes }}</p>
            @endif
        </div>
        <div class="card-footer">
            <a wire:navigate href="{{ route('plans.edit', $plan) }}" class="btn btn-teal btn-sm">
                <i class="fas fa-pen mr-1"></i> Editar plano
            </a>
            <a wire:navigate href="{{ route('plans.index') }}" class="btn btn-default btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Voltar
            </a>
        </div>
    </div>

    {{-- Semanas --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-calendar-week mr-2"></i>Semanas e sessões</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-sm btn-teal" wire:click="openWeekForm">
                    <i class="fas fa-plus mr-1"></i> Nova semana
                </button>
            </div>
        </div>

        <div class="card-body">
            @if ($showWeekForm)
                <div class="card card-outline card-info mb-3">
                    <div class="card-header">
                        <h5 class="card-title">{{ $editingWeekId ? 'Editar semana' : 'Nova semana' }}</h5>
                    </div>
                    <form wire:submit="saveWeek">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="week_number">Nº *</label>
                                        <input type="number" min="1" class="form-control @error('week.week_number') is-invalid @enderror"
                                            id="week_number" wire:model="week.week_number">
                                        @error('week.week_number')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="week_name">Nome</label>
                                        <input type="text" class="form-control @error('week.name') is-invalid @enderror"
                                            id="week_name" wire:model="week.name" placeholder="Ex.: Semana de adaptação">
                                        @error('week.name')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="week_start">Início *</label>
                                        <input type="date" class="form-control @error('week.start_date') is-invalid @enderror"
                                            id="week_start" wire:model="week.start_date">
                                        @error('week.start_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="week_end">Fim *</label>
                                        <input type="date" class="form-control @error('week.end_date') is-invalid @enderror"
                                            id="week_end" wire:model="week.end_date">
                                        @error('week.end_date')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group mb-0">
                                        <label for="week_objective">Objetivo da semana</label>
                                        <input type="text" class="form-control @error('week.objective') is-invalid @enderror"
                                            id="week_objective" wire:model="week.objective" placeholder="Ex.: Construir base aeróbica">
                                        @error('week.objective')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-teal btn-sm" wire:loading.attr="disabled" wire:target="saveWeek">
                                <span wire:loading.remove wire:target="saveWeek"><i class="fas fa-save mr-1"></i> Salvar</span>
                                <span wire:loading wire:target="saveWeek"><i class="fas fa-spinner fa-spin"></i></span>
                            </button>
                            <button type="button" class="btn btn-default btn-sm" wire:click="closeWeekForm">Cancelar</button>
                        </div>
                    </form>
                </div>
            @endif

            @if ($plan->weeks->count() > 0)
                @foreach ($plan->weeks as $weekItem)
                    <div class="card card-outline card-secondary mb-2" wire:key="week-{{ $weekItem->id }}">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <strong>Semana {{ $weekItem->week_number }}</strong>
                                @if ($weekItem->name) — {{ $weekItem->name }} @endif
                                <span class="badge badge-info ml-2">{{ $weekStatus[$weekItem->status->value] }}</span>
                                <small class="text-muted ml-2">
                                    {{ $weekItem->start_date?->format('d/m/Y') ?? '—' }}
                                    → {{ $weekItem->end_date?->format('d/m/Y') ?? '—' }}
                                </small>
                                <small class="text-muted ml-2">({{ $weekItem->sessions->count() }} sessões)</small>
                            </h5>
                            <div class="card-tools">
                                <button type="button" class="btn btn-tool" wire:click="toggleSessions({{ $weekItem->id }})"
                                    title="Ver sessões">
                                    <i class="fas {{ $expandedWeekId === $weekItem->id ? 'fa-minus' : 'fa-plus' }}"></i>
                                </button>
                                <button type="button" class="btn btn-tool" wire:click="editWeek({{ $weekItem->id }})" title="Editar semana">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <button type="button" class="btn btn-tool text-danger" wire:click="deleteWeek({{ $weekItem->id }})"
                                    wire:confirm="Excluir a semana {{ $weekItem->week_number }} e todas as suas sessões?" title="Excluir semana">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        @if ($expandedWeekId === $weekItem->id)
                            <div class="card-body">
                                @if ($weekItem->objective)
                                    <p class="text-muted mb-2"><em>Objetivo: {{ $weekItem->objective }}</em></p>
                                @endif

                                @if ($showSessionForm && $sessionWeekId === $weekItem->id)
                                    <div class="card card-outline card-info mb-3">
                                        <div class="card-header">
                                            <h6 class="card-title">{{ $editingSessionId ? 'Editar sessão' : 'Nova sessão' }}</h6>
                                        </div>
                                        <form wire:submit="saveSession">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label for="sport_id">Modalidade *</label>
                                                            <select class="form-control @error('session.sport_id') is-invalid @enderror"
                                                                id="sport_id" wire:model="session.sport_id">
                                                                <option value="">Selecione...</option>
                                                                @foreach ($sports as $sport)
                                                                    <option value="{{ $sport->id }}">{{ $sport->name }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error('session.sport_id')
                                                                <span class="invalid-feedback">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-5">
                                                        <div class="form-group">
                                                            <label for="session_title">Título *</label>
                                                            <input type="text" class="form-control @error('session.title') is-invalid @enderror"
                                                                id="session_title" wire:model="session.title" placeholder="Ex.: Corrida contínua 10km">
                                                            @error('session.title')
                                                                <span class="invalid-feedback">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label for="session_date">Data *</label>
                                                            <input type="date" class="form-control @error('session.scheduled_date') is-invalid @enderror"
                                                                id="session_date" wire:model="session.scheduled_date">
                                                            @error('session.scheduled_date')
                                                                <span class="invalid-feedback">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>

                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label for="session_duration">Duração (min)</label>
                                                            <input type="number" min="0" class="form-control @error('session.estimated_duration') is-invalid @enderror"
                                                                id="session_duration" wire:model="session.estimated_duration">
                                                            @error('session.estimated_duration')
                                                                <span class="invalid-feedback">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label for="session_distance">Distância (m)</label>
                                                            <input type="number" min="0" class="form-control @error('session.distance') is-invalid @enderror"
                                                                id="session_distance" wire:model="session.distance" placeholder="Ex.: 10000">
                                                            @error('session.distance')
                                                                <span class="invalid-feedback">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label for="session_intensity">Intensidade</label>
                                                            <input type="text" class="form-control @error('session.intensity') is-invalid @enderror"
                                                                id="session_intensity" wire:model="session.intensity" placeholder="Ex.: Fácil, Ritmo, Intervalado">
                                                            @error('session.intensity')
                                                                <span class="invalid-feedback">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label for="session_pace">Ritmo alvo</label>
                                                            <input type="text" class="form-control @error('session.target_pace') is-invalid @enderror"
                                                                id="session_pace" wire:model="session.target_pace" placeholder="Ex.: 5:30/km">
                                                            @error('session.target_pace')
                                                                <span class="invalid-feedback">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="session_description">Descrição</label>
                                                            <textarea class="form-control @error('session.description') is-invalid @enderror"
                                                                id="session_description" rows="2" wire:model="session.description"></textarea>
                                                            @error('session.description')
                                                                <span class="invalid-feedback">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="session_instructions">Instruções</label>
                                                            <textarea class="form-control @error('session.instructions') is-invalid @enderror"
                                                                id="session_instructions" rows="2" wire:model="session.instructions"></textarea>
                                                            @error('session.instructions')
                                                                <span class="invalid-feedback">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-md-12">
                                                        <div class="form-group mb-0">
                                                            <label for="session_coach_notes">Notas do treinador</label>
                                                            <textarea class="form-control @error('session.coach_notes') is-invalid @enderror"
                                                                id="session_coach_notes" rows="2" wire:model="session.coach_notes"></textarea>
                                                            @error('session.coach_notes')
                                                                <span class="invalid-feedback">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="card-footer">
                                                <button type="submit" class="btn btn-teal btn-sm" wire:loading.attr="disabled" wire:target="saveSession">
                                                    <span wire:loading.remove wire:target="saveSession"><i class="fas fa-save mr-1"></i> Salvar sessão</span>
                                                    <span wire:loading wire:target="saveSession"><i class="fas fa-spinner fa-spin"></i></span>
                                                </button>
                                                <button type="button" class="btn btn-default btn-sm" wire:click="closeSessionForm">Cancelar</button>
                                            </div>
                                        </form>
                                    </div>
                                @endif

                                @if ($weekItem->sessions->count() > 0)
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead>
                                            <tr>
                                                <th>Data</th>
                                                <th>Sessão</th>
                                                <th>Modalidade</th>
                                                <th class="text-center">Duração</th>
                                                <th class="text-center">Distância</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($weekItem->sessions as $sessionItem)
                                                <tr wire:key="session-{{ $sessionItem->id }}">
                                                    <td>{{ $sessionItem->scheduled_date?->format('d/m') }}</td>
                                                    <td>
                                                        {{ $sessionItem->title }}
                                                        @if ($sessionItem->intensity)
                                                            <br><small class="text-muted">{{ $sessionItem->intensity }}</small>
                                                        @endif
                                                    </td>
                                                    <td>{{ $sessionItem->sport?->name ?? '—' }}</td>
                                                    <td class="text-center">{{ $sessionItem->estimated_duration ? $sessionItem->estimated_duration . ' min' : '—' }}</td>
                                                    <td class="text-center">{{ $sessionItem->distance ? number_format($sessionItem->distance / 1000, 2, ',', '.') . ' km' : '—' }}</td>
                                                    <td class="text-center">
                                                        <span class="badge {{ $sessionItem->status->value === 'completed' ? 'badge-success' : ($sessionItem->status->value === 'planned' ? 'badge-info' : 'badge-secondary') }}">
                                                            {{ $sessionStatus[$sessionItem->status->value] }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-xs btn-default"
                                                            wire:click="editSession({{ $sessionItem->id }})" title="Editar">
                                                            <i class="fas fa-pen"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-xs btn-danger text-white"
                                                            wire:click="deleteSession({{ $sessionItem->id }})"
                                                            wire:confirm="Excluir esta sessão?" title="Excluir">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <p class="text-muted mb-0">Nenhuma sessão nesta semana.</p>
                                @endif
                            </div>
                            <div class="card-footer">
                                <button type="button" class="btn btn-sm btn-info" wire:click="openSessionForm({{ $weekItem->id }})">
                                    <i class="fas fa-plus mr-1"></i> Nova sessão
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="alert alert-info mb-0">
                    Nenhuma semana cadastrada. Clique em <strong>Nova semana</strong> para começar a montar o treino.
                </div>
            @endif
        </div>
    </div>
</div>
