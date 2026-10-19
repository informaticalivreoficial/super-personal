<?php

namespace App\Services;

use App\Enums\PaymentChannel;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Enums\TrainingSessionStatus;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;

class DashboardService
{
    public function __construct(
        private PaymentService $payments,
        private PayoutService $payouts,
    ) {}

    /**
     * Visão geral do treinador (tenant autenticado).
     *
     * O treinador é o do usuário autenticado; as consultas de domínio saem
     * filtradas pelo global scope `tenant` (isolamento). As chaves `money` e
     * as listas alimentam o painel; `payments` é mantido como contrato da API
     * (app/API do professor).
     *
     * @return array<string, mixed>
     */
    public function teacherDashboard(?Teacher $teacher = null): array
    {
        $teacher ??= auth()->user()?->teacher;
        $today = now()->toDateString();

        // "Em atraso" = marcada como vencida OU pendente com vencimento no
        // passado (mesma definição do dashboard da plataforma).
        $overdue = fn () => Payment::query()->where(fn ($query) => $query
            ->where('status', PaymentStatus::OVERDUE)
            ->orWhere(fn ($pending) => $pending
                ->where('status', PaymentStatus::PENDING)
                ->whereDate('due_date', '<', $today)));

        $paidThisMonth = (float) Payment::where('status', PaymentStatus::PAID)
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        return [
            'students' => [
                'total' => Student::where('active', true)->count(),
            ],
            'plans' => [
                'active' => TrainingPlan::where('status', 'active')->count(),
            ],
            'trainings' => [
                'today' => TrainingSession::whereDate('scheduled_date', $today)->count(),
                'week' => TrainingSession::whereBetween('scheduled_date', [
                    now()->startOfWeek()->toDateString(),
                    now()->endOfWeek()->toDateString(),
                ])->count(),
                'pending' => TrainingSession::where('status', TrainingSessionStatus::PLANNED)
                    ->whereDate('scheduled_date', '>=', $today)
                    ->count(),
            ],
            'payments' => [
                'pending_amount' => (float) Payment::where('status', PaymentStatus::PENDING)->sum('amount'),
                'overdue_count' => (int) $overdue()->count(),
                'paid_this_month_amount' => $paidThisMonth,
            ],
            // Dinheiro do mês: o que o treinador tem a receber, o que recebeu e
            // o que a plataforma ainda vai repassar (líquido das cobranças
            // online pagas), além do consumo da cota de cobranças manuais.
            'money' => [
                'open_amount' => (float) Payment::whereIn('status', [
                    PaymentStatus::PENDING,
                    PaymentStatus::OVERDUE,
                ])->sum('amount'),
                'overdue_amount' => (float) $overdue()->sum('amount'),
                'received_this_month_amount' => $paidThisMonth,
                'awaiting_payout_amount' => $teacher ? $this->payouts->balance($teacher) : 0.0,
                'awaiting_release_amount' => $teacher ? $this->payouts->releasingTotal($teacher) : 0.0,
                'paid_out_total' => $teacher ? $this->payouts->paidTotal($teacher) : 0.0,
                'manual_this_month' => $teacher ? $this->payments->manualCountThisMonth($teacher->id) : 0,
                'manual_limit' => PaymentService::MANUAL_MONTHLY_LIMIT,
            ],
            // Listas acionáveis do dia.
            'overdue_payments' => $overdue()
                ->with('student')
                ->orderBy('due_date')
                ->limit(5)
                ->get(),
            'today_sessions' => TrainingSession::with(['student', 'sport'])
                ->whereDate('scheduled_date', $today)
                ->orderBy('sport_id')
                ->orderBy('id')
                ->limit(8)
                ->get(),
        ];
    }

    /**
     * Visão geral da plataforma (admin) — tenants, alunos e comissões.
     * Só é chamada para isPlatformAdmin(); os modelos com escopo `tenant`
     * não filtram para admin (acesso global).
     *
     * @return array<string, mixed>
     */
    public function platformDashboard(): array
    {
        return [
            'teachers' => [
                'total' => Teacher::count(),
                'active' => Teacher::where('active', true)->count(),
                'new_this_month' => Teacher::whereBetween('created_at', [
                    now()->startOfMonth(),
                    now()->endOfMonth(),
                ])->count(),
            ],
            'students' => [
                'total' => Student::count(),
                'active' => Student::where('active', true)->count(),
            ],
            // Receita da plataforma = comissão retida em cada cobrança ONLINE
            // paga pelo aluno (não existe assinatura/mensalidade do treinador).
            'commissions' => [
                'month_amount' => (float) Payment::where('channel', PaymentChannel::ONLINE)
                    ->where('status', PaymentStatus::PAID)
                    ->whereMonth('paid_at', now()->month)
                    ->whereYear('paid_at', now()->year)
                    ->sum('fee_amount'),
                'total_amount' => (float) Payment::where('channel', PaymentChannel::ONLINE)
                    ->where('status', PaymentStatus::PAID)
                    ->sum('fee_amount'),
                'paid_count' => Payment::where('channel', PaymentChannel::ONLINE)
                    ->where('status', PaymentStatus::PAID)
                    ->count(),
            ],
            // Repasses devidos aos treinadores (solicitado = a pagar).
            'payouts' => [
                'requested_amount' => (float) Payout::where('status', PayoutStatus::REQUESTED)->sum('net_amount'),
                'requested_count' => Payout::where('status', PayoutStatus::REQUESTED)->count(),
                'paid_amount' => (float) Payout::where('status', PayoutStatus::PAID)->sum('net_amount'),
            ],
            'trainings' => [
                'today' => TrainingSession::whereDate('scheduled_date', now()->toDateString())->count(),
            ],
            'payments' => [
                'overdue_count' => Payment::where('status', PaymentStatus::OVERDUE)
                    ->orWhere(function ($query) {
                        $query->where('status', PaymentStatus::PENDING)
                            ->where('due_date', '<', now()->toDateString());
                    })
                    ->count(),
            ],
        ];
    }

    /**
     * Visão geral do aluno autenticado.
     *
     * @return array<string, mixed>
     */
    public function studentDashboard(): array
    {
        $student = auth()->user()->student;

        abort_if(! $student, 403, 'Perfil de aluno não encontrado.');

        $today = now()->toDateString();

        return [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'goal' => $student->goal,
            ],
            'trainings' => [
                'today' => $student->trainingSessions()
                    ->whereDate('scheduled_date', $today)
                    ->where('status', TrainingSessionStatus::PLANNED)
                    ->count(),
                'next' => $student->trainingSessions()
                    ->whereDate('scheduled_date', '>', $today)
                    ->where('status', TrainingSessionStatus::PLANNED)
                    ->orderBy('scheduled_date')
                    ->limit(5)
                    ->count(),
                'completed_this_month' => $student->trainingSessions()
                    ->where('status', TrainingSessionStatus::COMPLETED)
                    ->whereMonth('scheduled_date', now()->month)
                    ->whereYear('scheduled_date', now()->year)
                    ->count(),
            ],
            'active_plans' => $student->trainingPlans()
                ->where('status', 'active')
                ->count(),
            'payments' => [
                'pending_count' => $student->payments()->where('status', PaymentStatus::PENDING)->count(),
                'pending_amount' => (float) $student->payments()->where('status', PaymentStatus::PENDING)->sum('amount'),
            ],
            'unread_notifications' => auth()->user()->unreadNotifications()->count(),
        ];
    }
}
