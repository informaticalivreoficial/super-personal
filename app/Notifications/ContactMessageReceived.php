<?php

namespace App\Notifications;

/**
 * Mensagem recebida pela página pública de atendimento.
 *
 * Entregue no canal `database` para cada administrador da plataforma — é o
 * registro consultável em `/admin/plataforma/atendimentos` (o e-mail e o
 * Telegram são disparados em paralelo pelo ContactService).
 */
class ContactMessageReceived extends BaseNotification
{
    public const TYPE = 'contact_message';

    /**
     * @param  array{name: string, email: string, phone?: ?string, subject?: ?string, message: string}  $payload
     */
    public static function fromRequest(array $payload): self
    {
        $subject = $payload['subject'] ?? null;

        return new self([
            'type' => self::TYPE,
            'title' => $subject !== null && $subject !== ''
                ? 'Atendimento: '.$subject
                : 'Nova mensagem de atendimento',
            'message' => $payload['message'],
            'name' => $payload['name'],
            'email' => $payload['email'],
            'phone' => $payload['phone'] ?? null,
            'subject' => $subject,
        ]);
    }
}
