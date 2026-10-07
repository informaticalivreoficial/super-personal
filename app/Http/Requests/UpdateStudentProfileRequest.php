<?php

namespace App\Http\Requests;

use App\Enums\StudentGender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edição do próprio perfil pelo aluno (campos seguros).
 * Campos de treino/avaliação são de responsabilidade do professor.
 */
class UpdateStudentProfileRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', Rule::enum(StudentGender::class)],
            'height' => ['nullable', 'integer', 'min:50', 'max:260'],
            'available_days' => ['nullable', 'array'],
            'available_days.*' => ['integer', 'min:1', 'max:7'],
        ];
    }
}
