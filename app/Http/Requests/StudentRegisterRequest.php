<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Registro do aluno no app com código de convite (POST /api/v1/auth/student-register).
 * Nome e e-mail vêm do cadastro feito pelo professor — só a senha é definida aqui.
 */
class StudentRegisterRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'invite_code' => ['required', 'string', 'max:16'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'invite_code.required' => 'O código de convite é obrigatório.',
            'invite_code.max' => 'O código de convite é inválido.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação de senha não confere.',
        ];
    }
}
