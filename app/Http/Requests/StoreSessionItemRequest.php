<?php

namespace App\Http\Requests;

use App\Enums\SessionItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Item de sessão (composição do treino).
 * Usado para criação e atualização (mesmo padrão de StorePaymentRequest).
 */
class StoreSessionItemRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $teacherId = auth()->user()?->resolveTenantId();

        return [
            'type' => ['required', Rule::enum(SessionItemType::class)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'exercise_id' => [
                'nullable',
                Rule::exists('exercises', 'id')
                    ->whereNull('deleted_at')
                    ->where(function ($query) use ($teacherId) {
                        $query->whereNull('teacher_id')->orWhere('teacher_id', $teacherId);
                    }),
            ],
            'duration' => ['nullable', 'integer', 'min:0'],
            'distance' => ['nullable', 'integer', 'min:0'],
            'repetitions' => ['nullable', 'integer', 'min:1', 'max:999'],
            'sets' => ['nullable', 'integer', 'min:1', 'max:999'],
            'rest' => ['nullable', 'integer', 'min:0'],
            'target' => ['nullable', 'string', 'max:30'],
            'intensity' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Selecione o tipo do item.',
            'title.required' => 'O título do item é obrigatório.',
        ];
    }
}
