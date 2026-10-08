<?php

namespace App\Http\Requests;

use App\Enums\ExerciseDifficulty;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExerciseRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $teacherId = auth()->user()?->resolveTenantId();

        $uniqueName = Rule::unique('exercises', 'name')
            ->ignore($this->input('exercise_id'))
            ->withoutTrashed();
        $uniqueName = $teacherId === null
            ? $uniqueName->whereNull('teacher_id')
            : $uniqueName->where('teacher_id', $teacherId);

        return [
            'sport_id' => ['sometimes', 'required', 'exists:sports,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255', $uniqueName],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url', 'string', 'max:255'],
            'difficulty' => ['nullable', Rule::enum(ExerciseDifficulty::class)],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sport_id.required' => 'Selecione a modalidade.',
            'name.required' => 'O nome do exercício é obrigatório.',
            'name.unique' => 'Você já tem um exercício ativo ou excluído com este nome.',
        ];
    }
}
