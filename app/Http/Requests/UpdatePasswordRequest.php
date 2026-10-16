<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

/**
 * Troca da própria senha pelo aluno (app): exige a senha atual e a repetição
 * da nova. A partir daí as outras sessões caem (o token que pediu continua).
 */
class UpdatePasswordRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', $this->currentPasswordRule()],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Informe sua senha atual.',
            'password.required' => 'Informe a nova senha.',
            'password.min' => 'A nova senha precisa ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da nova senha não confere.',
        ];
    }

    private function currentPasswordRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $user = $this->user();

            if ($user === null || ! Hash::check((string) $value, (string) $user->password)) {
                $fail('A senha atual não confere.');
            }
        };
    }
}
