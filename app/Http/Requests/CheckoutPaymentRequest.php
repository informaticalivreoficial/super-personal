<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Checkout da cobrança online do aluno (Pix).
 * O CPF é opcional: sem ele, usa o documento do cadastro do aluno.
 */
class CheckoutPaymentRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'cpf' => ['nullable', 'string', function (string $attribute, mixed $value, \Closure $fail) {
                if (filled($value) && strlen(preg_replace('/\D/', '', (string) $value)) !== 11) {
                    $fail('Informe um CPF válido (11 dígitos).');
                }
            }],
        ];
    }
}
