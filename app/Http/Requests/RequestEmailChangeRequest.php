<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Pedido de troca do e-mail de acesso pelo próprio aluno.
 *
 * A regra de "e-mail já em uso" (em `users` ou em `students`) e a de "diferente
 * do atual" ficam no `ProfileService` — lá o endereço é conferido também na
 * confirmação do código.
 */
class RequestEmailChangeRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Informe o novo e-mail.',
            'email.email' => 'Informe um e-mail válido.',
        ];
    }
}
