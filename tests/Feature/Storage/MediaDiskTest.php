<?php

namespace Tests\Feature\Storage;

use App\Http\Resources\StudentResource;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * O disco `public` é o disco de mídia do produto: local em dev/testes e
 * S3-compatível (Cloudflare R2) em produção, sem mudar o código chamador.
 */
class MediaDiskTest extends TestCase
{
    use RefreshDatabase;

    private function useR2Disk(): void
    {
        config([
            'filesystems.disks.public' => [
                'driver' => 's3',
                'key' => 'chave-de-teste',
                'secret' => 'segredo-de-teste',
                'region' => 'auto',
                'bucket' => 'infolivre-public',
                'root' => 'superpersonal',
                'url' => 'https://cdn.example.test',
                'endpoint' => 'https://conta.r2.cloudflarestorage.com',
                'use_path_style_endpoint' => false,
            ],
        ]);

        Storage::forgetDisk('public');
    }

    public function test_local_disk_is_the_default_for_media(): void
    {
        $this->assertSame('local', config('filesystems.disks.public.driver'));
        $this->assertSame(storage_path('app/public'), config('filesystems.disks.public.root'));
    }

    public function test_r2_url_includes_the_root_prefix(): void
    {
        $this->useR2Disk();

        $this->assertSame(
            'https://cdn.example.test/superpersonal/students/1/profile_photo.jpg',
            Storage::disk('public')->url('students/1/profile_photo.jpg')
        );
    }

    public function test_student_resource_uses_the_remote_url_for_photos(): void
    {
        $this->useR2Disk();

        $student = Student::factory()->forTeacher(Teacher::factory()->create())->create([
            'profile_photo' => 'students/1/profile_photo.jpg',
        ]);

        $url = (new StudentResource($student))
            ->toArray(Request::create('http://192.168.0.10'))['profile_photo_url'];

        $this->assertSame(
            'https://cdn.example.test/superpersonal/students/1/profile_photo.jpg',
            $url
        );
    }
}
