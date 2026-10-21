<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Extensão canônica para arquivos de mídia.
 *
 * Nunca confiar em `getClientOriginalExtension()`: o cliente controla o nome
 * e pode enviar `foto.php` com conteúdo de imagem. A extensão é derivada do
 * MIME real (fileinfo) e restrita a uma allowlist.
 */
class MediaFile
{
    /** Extensões de imagem aceitas no sistema. */
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    /**
     * Extensão segura (sem ponto) para o arquivo enviado.
     *
     * @param  array<int, string>  $allowed
     */
    public static function extension(UploadedFile $file, array $allowed = self::IMAGE_EXTENSIONS): string
    {
        // `extension()` usa o MIME detectado (fileinfo), não o nome original.
        $extension = strtolower((string) $file->extension());

        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        return in_array($extension, $allowed, true) ? $extension : 'jpg';
    }
}
