<?php

namespace Tests\Feature\Panel;

use App\Livewire\Dashboard\Students\StudentNotes;
use App\Models\Student;
use App\Models\StudentNote;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class StudentNotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_show_renders_notes_card_with_existing_note(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        StudentNote::factory()->forStudent($student)
            ->create(['content' => 'Aluno evoluiu na prova de fim de semana.']);

        $this->actingAs($teacher->user);

        $this->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('Observações')
            ->assertSee('Aluno evoluiu na prova de fim de semana.');
    }

    public function test_teacher_creates_note(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        Livewire::test(StudentNotes::class, ['student' => $student])
            ->set('content', 'Revisar cadência na corrida.')
            ->set('visibility', 'shared')
            ->call('saveNote')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('student_notes', [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'content' => 'Revisar cadência na corrida.',
            'visibility' => 'shared',
        ]);
    }

    public function test_note_requires_content_and_valid_visibility(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->actingAs($teacher->user);

        Livewire::test(StudentNotes::class, ['student' => $student])
            ->set('content', null)
            ->set('visibility', 'public')
            ->call('saveNote')
            ->assertHasErrors(['content', 'visibility']);

        $this->assertDatabaseCount('student_notes', 0);
    }

    public function test_teacher_deletes_own_note(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $note = StudentNote::factory()->forStudent($student)->create();

        $this->actingAs($teacher->user);

        Livewire::test(StudentNotes::class, ['student' => $student])
            ->call('deleteNote', $note->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('student_notes', ['id' => $note->id]);
    }

    public function test_delete_note_not_belonging_to_viewed_student_returns_404(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $otherNote = StudentNote::factory()
            ->forStudent(Student::factory()->forTeacher($teacher)->create())
            ->create();

        $this->actingAs($teacher->user);
        $this->withoutExceptionHandling();

        $this->expectException(NotFoundHttpException::class);

        Livewire::test(StudentNotes::class, ['student' => $student])
            ->call('deleteNote', $otherNote->id);
    }

    public function test_component_denies_student_of_another_tenant(): void
    {
        $teacherA = Teacher::factory()->create();
        $studentB = Student::factory()->forTeacher(Teacher::factory()->create())->create();

        $this->actingAs($teacherA->user);
        $this->withoutExceptionHandling();

        $this->expectException(AuthorizationException::class);

        Livewire::test(StudentNotes::class, ['student' => $studentB]);
    }

    public function test_admin_creates_note_preserving_student_ownership(): void
    {
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();
        $admin = User::factory(['role' => 'admin'])->create();

        $this->actingAs($admin);

        Livewire::test(StudentNotes::class, ['student' => $student])
            ->set('content', 'Observação do admin.')
            ->call('saveNote')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('student_notes', [
            'teacher_id' => $teacher->id,
            'student_id' => $student->id,
            'content' => 'Observação do admin.',
        ]);
    }
}
