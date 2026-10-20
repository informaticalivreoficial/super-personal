<?php

namespace Tests\Feature\Panel;

use App\Livewire\Dashboard\Students\StudentShow;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\TrainingPlan;
use App\Models\User;
use App\Services\StudentProfilePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StudentPdfTest extends TestCase
{
    use RefreshDatabase;

    private function htmlFor(Student $student): string
    {
        $service = app(StudentProfilePdfService::class);

        return view('pdf.student-profile', $service->data($student))->render();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $student = Student::factory()->forTeacher(Teacher::factory()->create())->create();

        $this->get(route('students.pdf', $student))->assertRedirect('/auth/login');
    }

    public function test_teacher_downloads_the_student_pdf(): void
    {
        $teacher = Teacher::factory()->create(['name' => 'Coach Renato', 'cref' => '123456-G/SP']);
        $student = Student::factory()->forTeacher($teacher)->create(['name' => 'João da Silva']);

        $this->actingAs($teacher->user);

        $response = $this->get(route('students.pdf', $student));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertDownload('ficha-aluno-joao-da-silva.pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_teacher_cannot_download_a_student_from_another_teacher(): void
    {
        $owner = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($owner)->create();

        $this->actingAs(Teacher::factory()->create()->user);

        $this->get(route('students.pdf', $student))->assertNotFound();
    }

    public function test_platform_admin_can_download_any_student_pdf(): void
    {
        $student = Student::factory()->forTeacher(Teacher::factory()->create())->create();

        $this->actingAs(User::factory(['role' => 'admin'])->create());

        $this->get(route('students.pdf', $student))->assertOk();
    }

    public function test_pdf_content_has_student_data_plan_and_payments(): void
    {
        $teacher = Teacher::factory()->create(['name' => 'Coach Renato', 'cref' => '123456-G/SP']);
        $student = Student::factory()->forTeacher($teacher)->create([
            'name' => 'Maria Souza',
            'email' => 'maria@example.test',
            'phone' => '(11) 91234-5678',
            'birth_date' => '1990-05-20',
            'document' => '123.456.789-00',
            'height' => 165,
            'current_weight' => 62.5,
            'target_weight' => 58,
            'fitness_level' => 'intermediate',
            'goal' => 'Correr 10 km',
            'available_days' => [2, 4, 6],
            'observations' => 'Treina melhor pela manhã.',
        ]);

        TrainingPlan::factory()->forStudent($student)->create([
            'name' => 'Base para 10 km',
            'status' => 'active',
        ]);

        Payment::factory()->forStudent($student)->create([
            'description' => 'Mensalidade de outubro',
            'status' => 'paid',
        ]);

        $html = $this->htmlFor($student);

        $this->assertStringContainsString('Maria Souza', $html);
        $this->assertStringContainsString('maria@example.test', $html);
        $this->assertStringContainsString('(11) 91234-5678', $html);
        $this->assertStringContainsString('123.456.789-00', $html);
        $this->assertStringContainsString('62,5 kg', $html);
        $this->assertStringContainsString('Correr 10 km', $html);
        $this->assertStringContainsString('Segunda, Quarta, Sexta', $html);
        $this->assertStringContainsString('Base para 10 km', $html);
        $this->assertStringContainsString('Mensalidade de outubro', $html);
        $this->assertStringContainsString('Coach Renato', $html);
        $this->assertStringContainsString('123456-G/SP', $html);
        $this->assertStringContainsString('Treina melhor pela manhã.', $html);
    }

    public function test_pdf_uses_initials_when_the_student_has_no_photo(): void
    {
        $student = Student::factory()->forTeacher(Teacher::factory()->create())->create([
            'name' => 'Renato Almeida',
            'profile_photo' => null,
        ]);

        $data = app(StudentProfilePdfService::class)->data($student);

        $this->assertNull($data['photo']);
        $this->assertStringContainsString('RA', $data['initials']);
        $this->assertStringContainsString('RA', $this->htmlFor($student));
    }

    public function test_profile_photo_is_embedded_as_data_uri(): void
    {
        Storage::fake('public');

        $student = Student::factory()->forTeacher(Teacher::factory()->create())->create([
            'name' => 'Renato Almeida',
        ]);

        Storage::disk('public')->put(
            'students/'.$student->id.'/profile_photo.jpg',
            UploadedFile::fake()->image('foto.jpg', 640, 640)->getContent()
        );

        $student->forceFill(['profile_photo' => 'students/'.$student->id.'/profile_photo.jpg'])->save();

        $data = app(StudentProfilePdfService::class)->data($student);

        $this->assertNotNull($data['photo']);
        $this->assertStringStartsWith('data:image/jpeg;base64,', $data['photo']);
        $this->assertStringContainsString('data:image/jpeg;base64,', $this->htmlFor($student));
    }

    public function test_student_show_links_to_the_pdf_and_shows_the_photo(): void
    {
        Storage::fake('public');

        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create(['name' => 'Renato Almeida']);

        Storage::disk('public')->put(
            'students/'.$student->id.'/profile_photo.jpg',
            UploadedFile::fake()->image('foto.jpg', 400, 400)->getContent()
        );

        $student->forceFill(['profile_photo' => 'students/'.$student->id.'/profile_photo.jpg'])->save();

        $this->actingAs($teacher->user);

        Livewire::test(StudentShow::class, ['student' => $student])
            ->assertOk()
            ->assertSee(route('students.pdf', $student), false)
            ->assertSee('Baixar PDF')
            ->assertSee('/storage/students/'.$student->id.'/profile_photo.jpg', false);
    }
}
