<?php

namespace App\Http\Requests;

use App\Enums\SessionItemType;
use App\Enums\TrainingSessionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrainingSessionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $teacherId = auth()->user()?->resolveTenantId();

        return [
            'sport_id' => ['sometimes', 'required', 'exists:sports,id'],
            'exercise_id' => [
                'nullable',
                Rule::exists('exercises', 'id')
                    ->whereNull('deleted_at')
                    ->where(function ($query) use ($teacherId) {
                        $query->whereNull('teacher_id')->orWhere('teacher_id', $teacherId);
                    }),
            ],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'scheduled_date' => ['sometimes', 'required', 'date'],
            'estimated_duration' => ['nullable', 'integer', 'min:0'],
            'distance' => ['nullable', 'integer', 'min:0'],
            'intensity' => ['nullable', 'string', 'max:30'],
            'target_pace' => ['nullable', 'string', 'max:30'],
            'target_heart_rate' => ['nullable', 'string', 'max:30'],
            'target_power' => ['nullable', 'string', 'max:30'],
            'instructions' => ['nullable', 'string'],
            'coach_notes' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::enum(TrainingSessionStatus::class)],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'items' => ['sometimes', 'array', 'max:50'],
            'items.*.type' => ['sometimes', Rule::enum(SessionItemType::class)],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.duration' => ['nullable', 'integer', 'min:0'],
            'items.*.distance' => ['nullable', 'integer', 'min:0'],
            'items.*.repetitions' => ['nullable', 'integer', 'min:1', 'max:999'],
            'items.*.sets' => ['nullable', 'integer', 'min:1', 'max:999'],
            'items.*.rest' => ['nullable', 'integer', 'min:0'],
            'items.*.target' => ['nullable', 'string', 'max:30'],
            'items.*.intensity' => ['nullable', 'string', 'max:30'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'items.*.exercise_id' => [
                'nullable',
                Rule::exists('exercises', 'id')
                    ->whereNull('deleted_at')
                    ->where(function ($query) use ($teacherId) {
                        $query->whereNull('teacher_id')->orWhere('teacher_id', $teacherId);
                    }),
            ],
        ];
    }
}
