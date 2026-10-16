<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Código de 6 dígitos enviado ao novo e-mail na troca de endereço.
 */
class ConfirmEmailChangeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'digits:6'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Informe o código que enviamos para o novo e-mail.',
            'code.digits' => 'O código tem 6 dígitos.',
        ];
    }
}
