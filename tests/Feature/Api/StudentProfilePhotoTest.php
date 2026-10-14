<?php

namespace Tests\Feature\Api;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    /** @var array{teacher: Teacher, student: Student} */
    private array $ctx;

    protected function setUp(): void
    {
        parent::setUp();

        $teacher = Teacher::factory()->create();
        $student = Student::factory()->forTeacher($teacher)->create();

        $this->ctx = compact('teacher', 'student');
    }

    public function test_student_uploads_profile_photo(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->ctx['student']->user);

        $response = $this->post('/api/v1/student/profile/photo', [
            'photo' => UploadedFile::fake()->image('foto.jpg', 400, 400),
        ], ['Accept' => 'application/json']);

        $response->assertOk();
        $path = 'students/'.$this->ctx['student']->id.'/profile_photo.jpg';
        $this->assertSame($path, $response->json('data.profile_photo'));
        Storage::disk('public')->assertExists($path);

        $url = $response->json('data.profile_photo_url');
        $this->assertStringContainsString('/storage/'.$path, $url);
    }

    public function test_upload_rejects_non_image(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->ctx['student']->user);

        $response = $this->post('/api/v1/student/profile/photo', [
            'photo' => UploadedFile::fake()->create('arquivo.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422)->assertJsonValidationErrors('photo');
        $this->assertNull($this->ctx['student']->fresh()->profile_photo);
    }

    public function test_upload_rejects_file_over_2mb(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->ctx['student']->user);

        $response = $this->post('/api/v1/student/profile/photo', [
            'photo' => UploadedFile::fake()->image('foto.jpg', 400, 400)->size(3000),
        ], ['Accept' => 'application/json']);

        $response->assertStatus(422)->assertJsonValidationErrors('photo');
    }

    public function test_upload_requires_photo_field(): void
    {
        Sanctum::actingAs($this->ctx['student']->user);

        $this->post('/api/v1/student/profile/photo', [], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('photo');
    }

    public function test_new_upload_replaces_old_file(): void
    {
        Storage::fake('public');
        $student = $this->ctx['student'];
        $oldPath = 'students/'.$student->id.'/profile_photo.jpg';
        Storage::disk('public')->put($oldPath, 'antiga');
        $student->update(['profile_photo' => $oldPath]);

        Sanctum::actingAs($student->user);
        $response = $this->post('/api/v1/student/profile/photo', [
            'photo' => UploadedFile::fake()->image('nova.png', 100, 100),
        ], ['Accept' => 'application/json']);

        $response->assertOk();
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists(
            'students/'.$student->id.'/profile_photo.png'
        );
    }

    public function test_student_removes_profile_photo(): void
    {
        Storage::fake('public');
        $student = $this->ctx['student'];
        $path = 'students/'.$student->id.'/profile_photo.jpg';
        Storage::disk('public')->put($path, 'foto');
        $student->update(['profile_photo' => $path]);

        Sanctum::actingAs($student->user);
        $response = $this->deleteJson('/api/v1/student/profile/photo');

        $response->assertOk()->assertJsonPath('data.profile_photo', null);
        Storage::disk('public')->assertMissing($path);
        $this->assertNull($student->fresh()->profile_photo);
    }

    public function test_remove_without_photo_still_succeeds(): void
    {
        Sanctum::actingAs($this->ctx['student']->user);

        $this->deleteJson('/api/v1/student/profile/photo')->assertOk();
    }

    public function test_teacher_cannot_upload_student_photo(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create(['role' => 'teacher']));

        $this->post('/api/v1/student/profile/photo', [
            'photo' => UploadedFile::fake()->image('foto.jpg', 100, 100),
        ], ['Accept' => 'application/json'])->assertStatus(403);
    }

    public function test_guest_cannot_upload_photo(): void
    {
        Storage::fake('public');

        $this->post('/api/v1/student/profile/photo', [
            'photo' => UploadedFile::fake()->image('foto.jpg', 100, 100),
        ], ['Accept' => 'application/json'])->assertStatus(401);
    }
}
