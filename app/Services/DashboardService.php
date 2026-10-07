<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\TrainingSessionStatus;
use App\Models\Payment;
use App\Models\Student;
use App\Models\TrainingPlan;
use App\Models\TrainingSession;

class DashboardService
{
    /**
     * Visão geral do professor (tenant autenticado).
     *
     * @return array<string, mixed>
     */
    public function teacherDashboard(): array
    {
        $today = now()->toDateString();

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
                'overdue_count' => Payment::where('status', PaymentStatus::OVERDUE)->count(),
                'paid_this_month_amount' => (float) Payment::where('status', PaymentStatus::PAID)
                    ->whereMonth('paid_at', now()->month)
                    ->whereYear('paid_at', now()->year)
                    ->sum('amount'),
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
