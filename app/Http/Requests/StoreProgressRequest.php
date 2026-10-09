<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Avaliação física/evolução registrada pelo treinador.
 * Cada chamada cria um novo registro no histórico (nunca sobrescreve).
 */
class StoreProgressRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'recorded_at' => ['required', 'date'],
            'weight' => ['nullable', 'numeric', 'min:20', 'max:400'],
            'body_fat' => ['nullable', 'numeric', 'min:0', 'max:80'],
            'measurements' => ['nullable', 'array'],
            'measurements.*' => ['numeric'],
            'resting_heart_rate' => ['nullable', 'integer', 'min:25', 'max:120'],
            'max_heart_rate' => ['nullable', 'integer', 'min:100', 'max:240'],
            'ftp' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'running_pace' => ['nullable', 'string', 'max:30'],
            'swimming_pace' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
