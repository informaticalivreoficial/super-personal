<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Dados do cartão no checkout transparente: o número/validade/CVV são
 * criptografados NO NAVEGADOR (SDK do gateway + chave pública da conta) e só
 * o hash (`encrypted`) chega aqui. `cpf` é higienizado no componente.
 */
class PayWithCardRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'encrypted' => ['required', 'string', 'max:2000'],
            'holder' => ['required', 'string', 'max:255'],
            'installments' => ['required', 'integer', 'min:1', 'max:12'],
            'cpf' => ['required', 'digits:11'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'encrypted.required' => 'Não foi possível ler os dados do cartão. Confira os campos e tente novamente.',
            'holder.required' => 'Informe o nome impresso no cartão.',
            'installments.required' => 'Selecione o número de parcelas.',
            'installments.integer' => 'Número de parcelas inválido.',
            'installments.min' => 'Número de parcelas inválido.',
            'installments.max' => 'O parcelamento pode ter no máximo 12x.',
            'cpf.required' => 'Informe o CPF do titular do cartão.',
            'cpf.digits' => 'O CPF deve conter 11 dígitos.',
        ];
    }
}
