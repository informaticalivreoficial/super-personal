<?php

namespace App\Http\Requests;

use App\Enums\SubscriptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Assinatura do treinador COM a plataforma (billing manual — sem gateway).
 * Reaproveitada no create e no update (não há restrição única).
 */
class StoreSubscriptionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'plan' => ['required', 'string', 'max:100'],
            'status' => ['required', Rule::enum(SubscriptionStatus::class)],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plan.required' => 'O plano é obrigatório.',
            'status.required' => 'O status da assinatura é obrigatório.',
            'amount.numeric' => 'O valor deve ser numérico.',
            'amount.min' => 'O valor deve ser maior ou igual a zero.',
            'ends_at.after_or_equal' => 'O fim deve ser depois ou igual ao início.',
        ];
    }
}
