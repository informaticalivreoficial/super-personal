<?php

namespace Tests\Feature\Panel;

use App\Livewire\Dashboard\Students\StudentForm;
use App\Livewire\Dashboard\Students\StudentIndex;
use App\Livewire\Dashboard\Students\StudentShow;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class StudentCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_lists_only_own_students(): void
    {
        $teacherA = Teacher::factory()->create();
        $teacherB = Teacher::factory()->create();
        Student::factory()->forTeacher($teacherA)->create(['name' => 'Aluno de A']);
        Student::factory()->forTeacher($teacherB)->create(['name' => 'Aluno de B']);

        $this->actingAs($teacherA->user);

        Livewire::test(StudentIndex::class)
            ->assertSee('Aluno de A')
            ->assertDontSee('Aluno de B');
    }

    public function test_teacher_creates_student(): void
    {
        $teacher = Teacher::factory()->create();

        $this->actingAs($teacher->user);

        Livewire::test(StudentForm::class)
            ->set('name', 'Maria Aluno')
            ->set('email', 'maria@aluno.test')
            ->set('phone', '(11) 99999-0000')
            ->set('fitness_level', 'beginner')
            ->set('available_days', [2, 4, 6])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('students', [
            'teacher_id' => $teacher->id,
            'name' => 'Maria Aluno',
            'email' => 'maria@aluno.test',
            'active' => true,
        ]);
    }

    public function test_duplicate_email_in_same_tenant_fails(): void
    {
        $teacher = Teacher::factory()->create();
        Student::factory()->forTeacher($teacher)->create(['email' => 'duplicado@aluno.test']);

        $this->actingAs($teacher->user);

        Livewire::test(StudentForm::class)
            ->set('name', 'Outro Aluno')
            ->set('email', 'duplicado@aluno.test')
            ->call('save')
            ->assertHasErrors(['email']);
    }

    public function test_teacher_can_update_own_student(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create(['name' => 'Nome Antigo']);

        $this->actingAs($teacher->user);

        Livewire::test(StudentForm::class, ['student' => $student])
            ->set('name', 'Nome Novo')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'Nome Novo',
        ]);
    }

    public function test_teacher_cannot_edit_student_from_another_tenant(): void
    {
        $teacherA = Teacher::factory()->create();
        $studentB = Student::factory()->forTeacher(Teacher::factory()->create())->create();

        $this->actingAs($teacherA->user);
        $this->withoutExceptionHandling();

        $this->expectException(AuthorizationException::class);

        Livewire::test(StudentForm::class, ['student' => $studentB]);
    }

    public function test_teacher_cannot_view_student_from_another_tenant(): void
    {
        $teacherA = Teacher::factory()->create();
        $studentB = Student::factory()->forTeacher(Teacher::factory()->create())->create();

        $this->actingAs($teacherA->user);
        $this->withoutExceptionHandling();

        $this->expectException(AuthorizationException::class);

        Livewire::test(StudentShow::class, ['student' => $studentB]);
    }

    public function test_student_from_another_tenant_returns_404_on_edit_route(): void
    {
        $teacherA = Teacher::factory()->create();
        $studentB = Student::factory()->forTeacher(Teacher::factory()->create())->create();

        $this->actingAs($teacherA->user)
            ->get(route('students.edit', $studentB))
            ->assertNotFound();
    }

    public function test_teacher_deletes_student_with_logical_exclusion(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create([
            'user_id' => User::factory(['role' => 'student', 'status' => 1])->create()->id,
        ]);

        $this->actingAs($teacher->user);

        Livewire::test(StudentIndex::class)
            ->call('confirmDelete', $student->id)
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertSoftDeleted('students', ['id' => $student->id]);

        $this->assertSame(0, (int) User::findOrFail($student->user_id)->status);
    }

    public function test_student_routes_render_for_teacher(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        $this->get(route('students.index'))->assertOk();
        $this->get(route('students.create'))->assertOk();
        $this->get(route('students.edit', $student))->assertOk();
        $this->get(route('students.show', $student))->assertOk();
    }

    public function test_guest_is_redirected_from_student_routes(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->get(route('students.index'))->assertRedirect('/auth/login');
        $this->get(route('students.show', $student))->assertRedirect('/auth/login');
    }

    public function test_platform_admin_cannot_create_students(): void
    {
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);
        $this->withoutExceptionHandling();

        // Policy dá bypass ao admin (before), o bloqueio é o guard de tenant no Service.
        $this->expectException(HttpException::class);

        Livewire::test(StudentForm::class)
            ->set('name', 'Aluno Teste')
            ->set('email', 'admin@aluno.test')
            ->call('save');
    }

    public function test_platform_admin_can_view_any_student(): void
    {
        $admin = User::factory(['role' => 'admin'])->create();
        $student = Student::factory()->forTeacher(Teacher::factory()->create())->create();

        $this->actingAs($admin);

        Livewire::test(StudentShow::class, ['student' => $student])
            ->assertSee($student->name);
    }
}
