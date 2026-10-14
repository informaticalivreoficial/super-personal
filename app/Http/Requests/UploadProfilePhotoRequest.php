<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Upload da foto de perfil do aluno (`POST /student/profile/photo`).
 *
 * Arquivo enviado por multipart; o caminho final é definido pelo Service
 * (nunca vem do request).
 */
class UploadProfilePhotoRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'image', 'max:2048'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.required' => 'Selecione uma foto.',
            'photo.image' => 'A foto deve ser uma imagem (PNG, JPG, WebP).',
            'photo.max' => 'A foto pode ter no máximo 2 MB.',
        ];
    }
}
