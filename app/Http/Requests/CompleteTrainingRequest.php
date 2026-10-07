<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Registro de conclusão/execução do treino pelo aluno.
 * Unidades canônicas: duração em segundos, distância em metros, RPE 1-10.
 */
class CompleteTrainingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'duration' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'distance' => ['nullable', 'integer', 'min:0'],
            'average_heart_rate' => ['nullable', 'integer', 'min:30', 'max:240'],
            'max_heart_rate' => ['nullable', 'integer', 'min:30', 'max:260'],
            'average_pace' => ['nullable', 'string', 'max:30'],
            'average_power' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'perceived_effort' => ['nullable', 'integer', 'min:1', 'max:10'],
            'feeling' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
