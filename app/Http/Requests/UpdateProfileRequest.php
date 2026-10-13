<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edição da conta do próprio usuário logado (página "Meu perfil").
 * O componente envia `user_id` para o ignore do unique do e-mail.
 * Senha é opcional: só altera se preenchida (confirmed + min 8).
 */
class UpdateProfileRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->input('user_id'))],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[+0-9()\s-]+$/'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Já existe um usuário com este e-mail.',
            'phone.regex' => 'Informe um telefone válido (números, espaços, parênteses e +).',
            'phone.max' => 'O telefone pode ter no máximo 30 caracteres.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'A confirmação de senha não confere.',
        ];
    }
}
