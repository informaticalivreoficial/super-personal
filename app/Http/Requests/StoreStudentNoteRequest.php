<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Observação do treinador sobre o aluno (student_notes).
 */
class StoreStudentNoteRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:5000'],
            'visibility' => ['required', 'in:private,shared'],
        ];
    }
}
