<?php

namespace Tests\Feature\Panel;

use App\Livewire\Dashboard\Students\StudentShow;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\StudentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class StudentInviteTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_generates_invite_code_for_student_without_account(): void
    {
        $teacher = Teacher::factory()->create();
        $this->actingAs($teacher->user);

        $student = app(StudentService::class)->store([
            'name' => 'Aluno Convite',
            'email' => 'aluno-convite@example.com',
        ]);

        $this->assertNotNull($student->invite_code);
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{8}$/', $student->invite_code);
    }

    public function test_student_show_displays_invite_code(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->withoutAccount()->forTeacher($teacher)->create();
        $student->invite_code = 'K7M2P9QX';
        $student->save();

        $this->actingAs($teacher->user);

        Livewire::test(StudentShow::class, ['student' => $student])
            ->assertOk()
            ->assertSee('Acesso no app')
            ->assertSee('K7M2P9QX');
    }

    public function test_regenerate_invite_changes_code_and_toasts(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->withoutAccount()->forTeacher($teacher)->create();
        $student->invite_code = 'K7M2P9QX';
        $student->save();

        $this->actingAs($teacher->user);

        Livewire::test(StudentShow::class, ['student' => $student])
            ->call('regenerateInvite')
            ->assertDispatched('toast');

        $student->refresh();
        $this->assertNotSame('K7M2P9QX', $student->invite_code);
        $this->assertMatchesRegularExpression('/^[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{8}$/', $student->invite_code);
    }

    public function test_student_with_account_shows_active_without_invite_actions(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create(); // já tem conta

        $this->actingAs($teacher->user);

        Livewire::test(StudentShow::class, ['student' => $student])
            ->assertOk()
            ->assertSee('Conta ativa')
            ->assertSee($student->email)
            ->assertDontSee('regenerateInvite', false);
    }

    public function test_service_rejects_regenerate_when_student_already_has_account(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        $this->expectException(ValidationException::class);
        app(StudentService::class)->regenerateInviteCode($student);
    }

    public function test_another_teacher_cannot_open_invite_page(): void
    {
        $teacherA = Teacher::factory()->create();
        $studentA = Student::factory()->withoutAccount()->forTeacher($teacherA)->create();
        $teacherB = Teacher::factory()->create();

        $this->actingAs($teacherB->user);

        $this->withoutExceptionHandling();
        $this->expectException(AuthorizationException::class);

        Livewire::test(StudentShow::class, ['student' => $studentA]);
    }
}
