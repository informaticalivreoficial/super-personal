<?php

namespace Tests\Feature\Console;

use App\Enums\PaymentStatus;
use App\Enums\TrainingSessionStatus;
use App\Enums\UserRole;
use App\Livewire\Dashboard\MyProfile;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Preferências de lembretes do treinador (Meu perfil) e efeito no comando.
 */
class ReminderPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private function runCommand(): void
    {
        $this->artisan('notifications:send-reminders')->assertSuccessful();
    }

    public function test_disabled_reminders_skip_payment_and_training_notifications(): void
    {
        $teacher = Teacher::factory()->create(['reminders_enabled' => false]);
        $student = Student::factory()->forTeacher($teacher)->create();

        Payment::factory()->forStudent($student)->create([
            'due_date' => now()->toDateString(),
            'status' => PaymentStatus::PENDING,
        ]);
        Payment::factory()->forStudent($student)->create([
            'due_date' => now()->subDays(3)->toDateString(),
            'status' => PaymentStatus::PENDING,
        ]);
        TrainingSession::factory()->create([
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'scheduled_date' => now()->toDateString(),
            'status' => TrainingSessionStatus::PLANNED,
        ]);

        $this->runCommand();

        $this->assertSame(0, DatabaseNotification::query()->count());
    }

    public function test_custom_lead_days_control_the_due_soon_reminder(): void
    {
        $teacher = Teacher::factory()->create(['reminder_due_days' => 7]);
        $student = Student::factory()->forTeacher($teacher)->create();

        Payment::factory()->forStudent($student)->create([
            'due_date' => now()->addDays(5)->toDateString(),
            'status' => PaymentStatus::PENDING,
        ]);

        $this->runCommand();

        $this->assertSame(1, DatabaseNotification::query()->count());
    }

    public function test_zero_lead_days_only_warns_on_the_due_date(): void
    {
        $teacher = Teacher::factory()->create(['reminder_due_days' => 0]);
        $student = Student::factory()->forTeacher($teacher)->create();

        Payment::factory()->forStudent($student)->create([
            'due_date' => now()->addDay()->toDateString(),
            'status' => PaymentStatus::PENDING,
        ]);

        $this->runCommand();
        $this->assertSame(0, DatabaseNotification::query()->count());

        Payment::factory()->forStudent($student)->create([
            'due_date' => now()->toDateString(),
            'status' => PaymentStatus::PENDING,
        ]);

        $this->runCommand();
        $this->assertSame(1, DatabaseNotification::query()->count());
    }

    public function test_teacher_can_change_reminder_preferences_in_my_profile(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(MyProfile::class)
            ->assertSee('Lembretes automáticos')
            ->set('remindersEnabled', false)
            ->set('reminderDueDays', 10)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $teacher->refresh();

        $this->assertFalse($teacher->reminders_enabled);
        $this->assertSame(10, $teacher->reminder_due_days);
    }

    public function test_lead_days_are_validated_between_zero_and_thirty(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(MyProfile::class)
            ->set('reminderDueDays', 31)
            ->call('save')
            ->assertHasErrors(['reminder_due_days']);

        $this->assertSame(3, $teacher->fresh()->reminder_due_days);
    }

    public function test_platform_admin_does_not_see_reminder_preferences(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => UserRole::ADMIN])->save();

        $this->actingAs($admin->fresh())
            ->get('/admin/meu-perfil')
            ->assertOk()
            ->assertDontSee('Lembretes automáticos');
    }
}
