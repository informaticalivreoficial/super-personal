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
 * O disco **padrão** (`FILESYSTEM_DISK`) é o de mídia do produto: `public`
 * (local) em dev/testes e `s3` (Cloudflare R2) em produção. O código chama
 * sempre `Storage::disk()`, então trocar o armazenamento é só mudar o .env.
 */
class MediaDiskTest extends TestCase
{
    use RefreshDatabase;

    private function useR2Disk(): void
    {
        config([
            'filesystems.default' => 's3',
            'filesystems.disks.s3' => [
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

        Storage::forgetDisk('s3');
    }

    public function test_local_media_disk_is_the_default(): void
    {
        $this->assertSame('public', config('filesystems.default'));
        $this->assertSame('local', config('filesystems.disks.public.driver'));
        $this->assertSame(storage_path('app/public'), config('filesystems.disks.public.root'));
    }

    public function test_r2_url_includes_the_root_prefix(): void
    {
        $this->useR2Disk();

        $this->assertSame(
            'https://cdn.example.test/superpersonal/students/1/profile_photo.jpg',
            Storage::disk()->url('students/1/profile_photo.jpg')
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
