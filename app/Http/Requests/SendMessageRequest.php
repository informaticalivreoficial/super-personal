<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Mensagem do professor enviada ao aluno (notificação database — app Android).
 */
class SendMessageRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }
}
