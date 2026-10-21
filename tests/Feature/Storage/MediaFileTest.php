<?php

namespace Tests\Feature\Storage;

use App\Support\MediaFile;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Extensão de mídia derivada do conteúdo (MIME), nunca do nome enviado.
 */
class MediaFileTest extends TestCase
{
    public function test_extension_ignores_a_dangerous_client_extension(): void
    {
        $file = UploadedFile::fake()->create('avatar.php', 10, 'image/png');

        $this->assertSame('png', MediaFile::extension($file));
    }

    public function test_extension_uses_the_allowed_mime_extension(): void
    {
        $file = UploadedFile::fake()->image('logo.jpeg', 20, 20);

        $this->assertSame('jpg', MediaFile::extension($file));
    }
}
