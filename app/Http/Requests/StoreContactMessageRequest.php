<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Mensagem enviada pelo formulário público de atendimento.
 *
 * O campo `website` fica fora das regras de propósito: é o honeypot
 * anti-spam (invisível para o visitante) e o controller apenas ignora o
 * envio quando ele vem preenchido — devolvendo a mesma confirmação de
 * sucesso, sem entregar pistas ao robô.
 */
class StoreContactMessageRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120', 'not_regex:/[\r\n]/'],
            'email' => ['required', 'string', 'email', 'max:180', 'not_regex:/[\r\n]/'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9()+\s.-]+$/'],
            'subject' => ['nullable', 'string', 'max:150', 'not_regex:/[\r\n]/'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'consent' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'email' => 'e-mail',
            'phone' => 'telefone',
            'subject' => 'assunto',
            'message' => 'mensagem',
            'consent' => 'autorização de contato',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe seu nome para que possamos responder.',
            'name.min' => 'O nome precisa ter pelo menos 2 caracteres.',
            'name.not_regex' => 'O nome não pode conter quebras de linha.',
            'email.required' => 'Informe um e-mail para contato.',
            'email.email' => 'Informe um e-mail válido.',
            'email.not_regex' => 'O e-mail não pode conter quebras de linha.',
            'phone.regex' => 'O telefone deve conter apenas números, espaços e os sinais ( ) + - .',
            'message.required' => 'Escreva sua mensagem.',
            'message.min' => 'Descreva sua mensagem com pelo menos 10 caracteres.',
            'message.max' => 'A mensagem pode ter no máximo 5.000 caracteres.',
            'consent.accepted' => 'É preciso autorizar o contato para enviar a mensagem.',
        ];
    }
}
