<?php

namespace App\Http\Requests;

use App\Enums\TrainingWeekStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWeekRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Rota (API) usa model binding; componente Livewire envia plan_id/week_id no payload.
        $planId = $this->route('plan')?->id ?? $this->input('plan_id');
        $weekId = $this->route('week')?->id ?? $this->input('week_id');

        return [
            'week_number' => [
                'required', 'integer', 'min:1', 'max:520',
                Rule::unique('training_weeks', 'week_number')
                    ->where('training_plan_id', $planId)
                    ->ignore($weekId),
            ],
            'name' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'objective' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::enum(TrainingWeekStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'week_number.unique' => 'Já existe uma semana com este número neste plano.',
        ];
    }
}
