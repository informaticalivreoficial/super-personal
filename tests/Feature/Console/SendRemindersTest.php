<?php

namespace Tests\Feature\Console;

use App\Enums\PaymentStatus;
use App\Enums\TrainingSessionStatus;
use App\Models\Payment;
use App\Models\Student;
use App\Models\TrainingSession;
use App\Notifications\PaymentDueSoon;
use App\Notifications\PaymentOverdue;
use App\Notifications\TrainingMissed;
use App\Notifications\TrainingToday;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class SendRemindersTest extends TestCase
{
    use RefreshDatabase;

    private function runCommand(): void
    {
        $this->artisan('notifications:send-reminders')->assertSuccessful();
    }

    private function notificationData(string $type): array
    {
        $notification = DatabaseNotification::query()
            ->where('type', $type)
            ->firstOrFail();

        return $notification->data;
    }

    public function test_due_soon_payment_receives_reminder(): void
    {
        $payment = Payment::factory()->create([
            'due_date' => now()->addDays(2),
            'status' => PaymentStatus::PENDING,
        ]);

        $this->runCommand();

        $data = $this->notificationData(PaymentDueSoon::class);

        $this->assertSame($payment->id, $data['payment_id']);
        $this->assertArrayHasKey('title', $data);
        $this->assertStringContainsString('vence em 2 dia(s)', $data['message']);

        $notification = DatabaseNotification::where('type', PaymentDueSoon::class)->firstOrFail();
        $this->assertSame($payment->student->user_id, $notification->notifiable_id);
    }

    public function test_payment_due_today_receives_reminder(): void
    {
        Payment::factory()->create([
            'due_date' => now()->toDateString(),
            'status' => PaymentStatus::PENDING,
        ]);

        $this->runCommand();

        $data = $this->notificationData(PaymentDueSoon::class);

        $this->assertStringContainsString('vence hoje', $data['message']);
    }

    public function test_overdue_payment_receives_reminder(): void
    {
        Payment::factory()->create([
            'due_date' => now()->subDays(5),
            'status' => PaymentStatus::PENDING,
        ]);

        $this->runCommand();

        $data = $this->notificationData(PaymentOverdue::class);

        $this->assertStringContainsString('venceu em', $data['message']);
        $this->assertStringContainsString('continua pendente', $data['message']);
    }

    public function test_paid_payment_is_not_notified(): void
    {
        Payment::factory()->paid()->create(['due_date' => now()->addDays(2)]);

        $this->runCommand();

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_payment_far_from_due_date_is_not_notified(): void
    {
        Payment::factory()->create([
            'due_date' => now()->addDays(10),
            'status' => PaymentStatus::PENDING,
        ]);

        $this->runCommand();

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_planned_session_today_receives_reminder(): void
    {
        $session = TrainingSession::factory()->create([
            'title' => 'Corrida intervalada',
            'scheduled_date' => now()->toDateString(),
            'status' => TrainingSessionStatus::PLANNED,
        ]);

        $this->runCommand();

        $data = $this->notificationData(TrainingToday::class);

        $this->assertSame($session->id, $data['session_id']);
        $this->assertStringContainsString('programado para hoje', $data['message']);

        $notification = DatabaseNotification::where('type', TrainingToday::class)->firstOrFail();
        $this->assertSame($session->student->user_id, $notification->notifiable_id);
    }

    public function test_completed_session_today_is_not_notified(): void
    {
        TrainingSession::factory()->create([
            'scheduled_date' => now()->toDateString(),
            'status' => TrainingSessionStatus::COMPLETED,
        ]);

        $this->runCommand();

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_yesterday_planned_session_receives_missed_reminder(): void
    {
        $session = TrainingSession::factory()->create([
            'title' => 'Natação técnica',
            'scheduled_date' => now()->subDay()->toDateString(),
            'status' => TrainingSessionStatus::PLANNED,
        ]);

        $this->runCommand();

        $data = $this->notificationData(TrainingMissed::class);

        $this->assertSame($session->id, $data['session_id']);
        $this->assertStringContainsString('não foi concluído', $data['message']);
    }

    public function test_session_tomorrow_is_not_notified(): void
    {
        TrainingSession::factory()->create([
            'scheduled_date' => now()->addDay()->toDateString(),
            'status' => TrainingSessionStatus::PLANNED,
        ]);

        $this->runCommand();

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_student_without_account_is_skipped(): void
    {
        $student = Student::factory()->withoutAccount()->create();

        Payment::factory()
            ->forStudent($student)
            ->create(['due_date' => now()->addDay()]);

        $this->runCommand();

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_reminders_are_not_duplicated_on_next_run(): void
    {
        Payment::factory()->create([
            'due_date' => now()->addDays(1),
            'status' => PaymentStatus::PENDING,
        ]);
        TrainingSession::factory()->create([
            'scheduled_date' => now()->toDateString(),
            'status' => TrainingSessionStatus::PLANNED,
        ]);

        $this->runCommand();
        $this->runCommand();

        $this->assertSame(1, DatabaseNotification::where('type', PaymentDueSoon::class)->count());
        $this->assertSame(1, DatabaseNotification::where('type', TrainingToday::class)->count());
        $this->assertDatabaseCount('notifications', 2);
    }
}
