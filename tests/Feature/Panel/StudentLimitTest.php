<?php

namespace Tests\Feature\Panel;

use App\Enums\SubscriptionStatus;
use App\Livewire\Dashboard\Students\StudentForm;
use App\Models\SaasPlan;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\Teacher;
use App\Services\StudentService;
use Database\Seeders\SaasPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class StudentLimitTest extends TestCase
{
    use RefreshDatabase;

    private function teacherWithPlan(string $slug): Teacher
    {
        (new SaasPlanSeeder)->run();

        $plan = SaasPlan::where('slug', $slug)->firstOrFail();

        $teacher = Teacher::factory()->create();
        Subscription::factory()->forTeacher($teacher)->create([
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::ACTIVE,
        ]);

        return $teacher;
    }

    public function test_teacher_without_subscription_is_not_blocked(): void
    {
        $teacher = Teacher::factory()->create();

        Student::factory()->count(15)->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        $student = app(StudentService::class)->store(['name' => 'Aluno Extra', 'email' => 'extra@test.dev']);

        $this->assertNotNull($student->id);
    }

    public function test_free_plan_blocks_above_limit(): void
    {
        $teacher = $this->teacherWithPlan('gratuito');

        Student::factory()->count(10)->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        $this->expectException(ValidationException::class);

        app(StudentService::class)->store(['name' => 'Aluno 11', 'email' => 'a11@test.dev']);
    }

    public function test_free_plan_allows_at_limit_minus_one(): void
    {
        $teacher = $this->teacherWithPlan('gratuito');

        Student::factory()->count(9)->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        $student = app(StudentService::class)->store(['name' => 'Aluno 10', 'email' => 'a10@test.dev']);

        $this->assertNotNull($student->id);
    }

    public function test_unlimited_plan_has_no_block(): void
    {
        $teacher = $this->teacherWithPlan('ilimitado');

        Student::factory()->count(30)->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        $student = app(StudentService::class)->store(['name' => 'Aluno 31', 'email' => 'a31@test.dev']);

        $this->assertNotNull($student->id);
    }

    public function test_expired_or_cancelled_subscription_does_not_block(): void
    {
        $teacher = $this->teacherWithPlan('gratuito');

        Student::factory()->count(10)->forTeacher($teacher)->create();

        $teacher->subscription->update(['status' => SubscriptionStatus::CANCELLED]);

        $this->actingAs($teacher->user);

        $student = app(StudentService::class)->store(['name' => 'Aluno Livre', 'email' => 'alivre@test.dev']);

        $this->assertNotNull($student->id);
    }

    public function test_livewire_form_shows_limit_error(): void
    {
        $teacher = $this->teacherWithPlan('gratuito');

        Student::factory()->count(10)->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        Livewire::test(StudentForm::class)
            ->set('name', 'Aluno 11')
            ->set('email', 'a11lw@test.dev')
            ->call('save')
            ->assertHasErrors(['student']);
    }

    public function test_soft_deleted_students_free_quota(): void
    {
        $teacher = $this->teacherWithPlan('gratuito');

        $students = Student::factory()->count(10)->forTeacher($teacher)->create();
        $students->last()->delete(); // soft delete libera vaga

        $this->actingAs($teacher->user);

        $student = app(StudentService::class)->store(['name' => 'Reposto', 'email' => 'reposto@test.dev']);

        $this->assertNotNull($student->id);
    }
}
