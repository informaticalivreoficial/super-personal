<?php

namespace App\Http\Requests;

use App\Enums\BillingPeriod;
use App\Enums\SubscriptionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Assinatura do treinador COM a plataforma (billing manual/admin).
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
            'plan_id' => ['required', 'integer', Rule::exists('saas_plans', 'id')],
            'period' => ['required', Rule::enum(BillingPeriod::class)],
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
            'plan_id.required' => 'O plano é obrigatório.',
            'plan_id.exists' => 'O plano selecionado não existe.',
            'period.required' => 'O período de cobrança é obrigatório.',
            'status.required' => 'O status da assinatura é obrigatório.',
            'amount.numeric' => 'O valor deve ser numérico.',
            'amount.min' => 'O valor deve ser maior ou igual a zero.',
            'ends_at.after_or_equal' => 'O fim deve ser depois ou igual ao início.',
        ];
    }
}
