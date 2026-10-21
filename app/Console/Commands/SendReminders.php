<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Enums\TrainingSessionStatus;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingSession;
use App\Models\User;
use App\Notifications\PaymentDueSoon;
use App\Notifications\PaymentOverdue;
use App\Notifications\TrainingMissed;
use App\Notifications\TrainingToday;
use Illuminate\Console\Command;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Lembretes automáticos ao aluno (canal database; push Android futuro no via()).
 *
 * Agendado diariamente às 07:00 (App\Console\Kernel). Roda sem autenticação,
 * então o global scope `tenant` não filtra (comando de plataforma).
 *
 * Deduplicação: uma notificação por evento — consulta em
 * `notifications.type` + `data->payment_id`/`data->session_id`.
 *
 * Preferências do treinador: `teachers.reminders_enabled` desliga os avisos do
 * tenant e `teachers.reminder_due_days` (0–30) define com quantos dias de
 * antecedência a cobrança "a vencer" é avisada.
 */
class SendReminders extends Command
{
    protected $signature = 'notifications:send-reminders';

    protected $description = 'Envia lembretes de pagamento (vence/venceu) e de treino (hoje/não realizado) aos alunos';

    public function handle(): int
    {
        $sent = $this->dueSoonPayments()
            + $this->overduePayments()
            + $this->todaySessions()
            + $this->missedSessions();

        $this->info("Lembretes enviados: {$sent}.");

        return self::SUCCESS;
    }

    /**
     * Pagamento pendente vencendo dentro da janela do treinador (inclusive hoje).
     * Busca o teto de 30 dias e aplica a preferência de cada tenant em memória
     * (evita funções de data específicas de driver na query).
     */
    private function dueSoonPayments(): int
    {
        $sent = 0;

        $payments = Payment::query()
            ->where('status', PaymentStatus::PENDING)
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->with(['student.user', 'teacher'])
            ->get();

        foreach ($payments as $payment) {
            if (! $this->remindersEnabled($payment->teacher)) {
                continue;
            }

            $window = $payment->due_date->startOfDay();

            if ($window->gt(now()->startOfDay()->addDays($this->reminderDueDays($payment->teacher)))) {
                continue;
            }

            if ($this->alreadySent(PaymentDueSoon::class, 'payment_id', $payment->id)) {
                continue;
            }

            $user = $this->recipient($payment->student);
            if (! $user) {
                continue;
            }

            $days = (int) now()->startOfDay()->diffInDays($payment->due_date->startOfDay());
            $prazo = $days <= 0 ? 'vence hoje' : "vence em {$days} dia(s)";

            $user->notify(new PaymentDueSoon([
                'title' => 'Pagamento próximo do vencimento',
                'message' => "O pagamento \"{$payment->description}\" {$prazo} ({$payment->due_date->format('d/m/Y')}).",
                'payment_id' => $payment->id,
                'student_id' => $payment->student_id,
                'teacher_id' => $payment->teacher_id,
                'amount' => (string) $payment->amount,
                'due_date' => $payment->due_date->toDateString(),
            ]));

            $sent++;
        }

        return $sent;
    }

    /** Pagamento com vencimento no passado e não pago (pendente/atribuído como atrasado). */
    private function overduePayments(): int
    {
        $sent = 0;

        $payments = Payment::query()
            ->whereIn('status', [PaymentStatus::PENDING, PaymentStatus::OVERDUE])
            ->where('due_date', '<', now()->toDateString())
            ->with(['student.user', 'teacher'])
            ->get();

        foreach ($payments as $payment) {
            if (! $this->remindersEnabled($payment->teacher)) {
                continue;
            }

            if ($this->alreadySent(PaymentOverdue::class, 'payment_id', $payment->id)) {
                continue;
            }

            $user = $this->recipient($payment->student);
            if (! $user) {
                continue;
            }

            $user->notify(new PaymentOverdue([
                'title' => 'Pagamento atrasado',
                'message' => "O pagamento \"{$payment->description}\" venceu em {$payment->due_date->format('d/m/Y')} e continua pendente.",
                'payment_id' => $payment->id,
                'student_id' => $payment->student_id,
                'teacher_id' => $payment->teacher_id,
                'amount' => (string) $payment->amount,
                'due_date' => $payment->due_date->toDateString(),
            ]));

            $sent++;
        }

        return $sent;
    }

    /** Sessão planejada para hoje. */
    private function todaySessions(): int
    {
        $sent = 0;

        $sessions = TrainingSession::query()
            ->where('status', TrainingSessionStatus::PLANNED)
            ->whereDate('scheduled_date', now()->toDateString())
            ->with(['student.user', 'teacher'])
            ->get();

        foreach ($sessions as $session) {
            if (! $this->remindersEnabled($session->teacher)) {
                continue;
            }

            if ($this->alreadySent(TrainingToday::class, 'session_id', $session->id)) {
                continue;
            }

            $user = $this->recipient($session->student);
            if (! $user) {
                continue;
            }

            $user->notify(new TrainingToday([
                'title' => 'Treino de hoje',
                'message' => "O treino \"{$session->title}\" está programado para hoje ({$session->scheduled_date->format('d/m/Y')}).",
                'session_id' => $session->id,
                'student_id' => $session->student_id,
                'teacher_id' => $session->teacher_id,
                'scheduled_date' => $session->scheduled_date->toDateString(),
            ]));

            $sent++;
        }

        return $sent;
    }

    /** Sessão de ontem que continuou como planejada (não realizada). */
    private function missedSessions(): int
    {
        $sent = 0;

        $sessions = TrainingSession::query()
            ->where('status', TrainingSessionStatus::PLANNED)
            ->whereDate('scheduled_date', now()->subDay()->toDateString())
            ->with(['student.user', 'teacher'])
            ->get();

        foreach ($sessions as $session) {
            if (! $this->remindersEnabled($session->teacher)) {
                continue;
            }

            if ($this->alreadySent(TrainingMissed::class, 'session_id', $session->id)) {
                continue;
            }

            $user = $this->recipient($session->student);
            if (! $user) {
                continue;
            }

            $user->notify(new TrainingMissed([
                'title' => 'Treino não realizado',
                'message' => "O treino \"{$session->title}\" de {$session->scheduled_date->format('d/m/Y')} ainda não foi concluído.",
                'session_id' => $session->id,
                'student_id' => $session->student_id,
                'teacher_id' => $session->teacher_id,
                'scheduled_date' => $session->scheduled_date->toDateString(),
            ]));

            $sent++;
        }

        return $sent;
    }

    /** Aluno com conta ativa (sem conta/login bloqueado → não notifica). */
    private function recipient(?Student $student): ?User
    {
        $user = $student?->user;

        if (! $user || $user->status != 1) {
            return null;
        }

        return $user;
    }

    /** O treinador quer receber lembretes? (sem perfil/legado = ligado). */
    private function remindersEnabled(?Teacher $teacher): bool
    {
        return $teacher === null || (bool) $teacher->reminders_enabled;
    }

    /** Antecedência da cobrança a vencer (0–30 dias; padrão 3). */
    private function reminderDueDays(?Teacher $teacher): int
    {
        return max(0, min(30, (int) ($teacher?->reminder_due_days ?? 3)));
    }

    /** Já enviou este evento? (evita reenviar a cada execução do cron). */
    private function alreadySent(string $type, string $key, int $id): bool
    {
        return DatabaseNotification::query()
            ->where('type', $type)
            ->where("data->{$key}", $id)
            ->exists();
    }
}
