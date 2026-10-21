<?php

namespace Tests\Feature\Panel;

use App\Enums\TrainingPlanStatus;
use App\Enums\UserRole;
use App\Livewire\Dashboard\Dashboard;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TeacherPayoutAccount;
use App\Models\TrainingPlan;
use App\Models\User;
use App\Services\OnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Checklist de primeiros passos do treinador no painel.
 */
class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_teacher_sees_the_onboarding_checklist_with_all_steps_pending(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Primeiros passos')
            ->assertSee('0 de 5 concluídos')
            ->assertSee('Cadastre seu primeiro aluno')
            ->assertSee('Configure como receber')
            ->assertSee('Coloque sua página pública no ar');
    }

    public function test_progress_counts_each_completed_step(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $plan = TrainingPlan::factory()->forStudent($student)->create([
            'status' => TrainingPlanStatus::DRAFT,
        ]);

        $progress = app(OnboardingService::class)->progress($teacher);

        $this->assertSame(2, $progress['done']);
        $this->assertSame(5, $progress['total']);
        $this->assertSame(40, $progress['percent']);
        $this->assertFalse($progress['complete']);
        $this->assertTrue($progress['visible']);

        $plan->update(['status' => TrainingPlanStatus::ACTIVE]);
        TeacherPayoutAccount::factory()->forTeacher($teacher)->create();
        $teacher->update(['public_profile' => true]);

        $progress = app(OnboardingService::class)->progress($teacher->fresh());

        $this->assertSame(5, $progress['done']);
        $this->assertSame(100, $progress['percent']);
        $this->assertTrue($progress['complete']);
        $this->assertFalse($progress['visible']);
    }

    public function test_card_disappears_when_every_step_is_done(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        TrainingPlan::factory()->forStudent($student)->create(['status' => TrainingPlanStatus::ACTIVE]);
        TeacherPayoutAccount::factory()->forTeacher($teacher)->create();
        $teacher->update(['public_profile' => true]);

        $this->actingAs($teacher->user)
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('Primeiros passos');
    }

    public function test_teacher_can_dismiss_the_checklist_and_it_stays_hidden(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(Dashboard::class)
            ->assertSee('Primeiros passos')
            ->call('dismissOnboarding')
            ->assertDispatched('toast')
            ->assertDontSee('Primeiros passos');

        $this->assertNotNull($teacher->fresh()->onboarding_dismissed_at);

        $this->get('/admin')->assertOk()->assertDontSee('Primeiros passos');
    }

    public function test_only_the_teacher_own_data_counts_towards_the_checklist(): void
    {
        $teacher = Teacher::factory()->create();
        $other = Teacher::factory()->create();
        $otherStudent = Student::factory()->forTeacher($other)->create();
        TrainingPlan::factory()->forStudent($otherStudent)->create(['status' => TrainingPlanStatus::ACTIVE]);
        TeacherPayoutAccount::factory()->forTeacher($other)->create();
        $other->update(['public_profile' => true]);

        $progress = app(OnboardingService::class)->progress($teacher);

        $this->assertSame(0, $progress['done']);
        $this->assertTrue($progress['visible']);
    }

    public function test_platform_admin_does_not_see_the_teacher_checklist(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => UserRole::ADMIN])->save();

        $this->actingAs($admin->fresh())
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('Primeiros passos');
    }
}
