<?php

namespace App\Http\Requests;

use App\Enums\BankAccountType;
use App\Enums\PayoutAccountType;
use App\Enums\PixKeyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Conta/chave de recebimento do treinador. Os campos exigidos variam com o
 * `type`: chave Pix pede chave + tipo da chave; conta bancária pede os dados
 * bancários. Usada no cadastro e na edição (mesmo payload).
 */
class PayoutAccountRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(PayoutAccountType::class)],
            'label' => ['nullable', 'string', 'max:60'],
            'is_default' => ['sometimes', 'boolean'],

            // Chave Pix
            'pix_key' => ['required_if:type,pix', 'nullable', 'string', 'max:255'],
            'pix_key_type' => ['required_if:type,pix', 'nullable', Rule::enum(PixKeyType::class)],

            // Conta bancária
            'bank_name' => ['required_if:type,bank_account', 'nullable', 'string', 'max:255'],
            'bank_code' => ['nullable', 'string', 'max:10'],
            'agency' => ['required_if:type,bank_account', 'nullable', 'string', 'max:20'],
            'account' => ['required_if:type,bank_account', 'nullable', 'string', 'max:30'],
            'account_digit' => ['nullable', 'string', 'max:5'],
            'account_type' => ['required_if:type,bank_account', 'nullable', Rule::enum(BankAccountType::class)],
            'holder_name' => ['nullable', 'string', 'max:255'],
            'holder_document' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Escolha o tipo de recebimento (Pix ou conta bancária).',
            'label.max' => 'O apelido deve ter no máximo 60 caracteres.',
            'pix_key.required_if' => 'Informe a chave Pix.',
            'pix_key_type.required_if' => 'Informe o tipo da chave Pix.',
            'bank_name.required_if' => 'Informe o banco.',
            'agency.required_if' => 'Informe a agência.',
            'account.required_if' => 'Informe o número da conta.',
            'account_type.required_if' => 'Informe o tipo da conta.',
        ];
    }
}
