<div>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark"><i class="fas fa-tachometer-alt mr-2"></i>Painel de Controle</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="javascript:void(0)">Início</a></li>
                        <li class="breadcrumb-item active">Painel de Controle</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="info-box">
                <span class="info-box-icon bg-teal">
                    <a href="{{ route('students.index') }}" title="Alunos">
                        <i class="fas fa-user-graduate"></i>
                    </a>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text"><b>Alunos ativos</b></span>
                    <span class="info-box-number">{{ $stats['students']['total'] }}</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="info-box">
                <span class="info-box-icon bg-info">
                    <i class="fas fa-calendar-check"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text"><b>Treinos hoje</b></span>
                    <span class="info-box-number">{{ $stats['trainings']['today'] }}</span>
                    <span class="info-box-text text-sm">Semana: {{ $stats['trainings']['week'] }}</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="info-box">
                <span class="info-box-icon bg-purple">
                    <i class="fas fa-clipboard-list"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text"><b>Planos ativos</b></span>
                    <span class="info-box-number">{{ $stats['plans']['active'] }}</span>
                    <span class="info-box-text text-sm">Pendentes: {{ $stats['trainings']['pending'] }}</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
            <div class="info-box">
                <span class="info-box-icon {{ $stats['payments']['overdue_count'] > 0 ? 'bg-danger' : 'bg-success' }}">
                    <i class="fas fa-dollar-sign"></i>
                </span>
                <div class="info-box-content">
                    <span class="info-box-text"><b>Pagamentos pendentes</b></span>
                    <span class="info-box-number">R$ {{ number_format($stats['payments']['pending_amount'], 2, ',', '.') }}</span>
                    @if ($stats['payments']['overdue_count'] > 0)
                        <span class="info-box-text text-sm text-danger">{{ $stats['payments']['overdue_count'] }} em atraso</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-teal">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bolt mr-2"></i>Acesso rápido</h3>
                </div>
                <div class="card-body">
                    <a wire:navigate href="{{ route('students.index') }}" class="btn btn-default mr-2 mb-2">
                        <i class="fas fa-user-graduate mr-1"></i> Alunos
                    </a>
                    <a wire:navigate href="{{ route('students.create') }}" class="btn btn-primary mr-2 mb-2">
                        <i class="fas fa-plus mr-1"></i> Novo aluno
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
