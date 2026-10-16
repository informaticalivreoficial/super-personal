<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Código de confirmação da troca de e-mail.
 *
 * Vai por e-mail (canal `mail`) para o **novo** endereço, enviada como
 * notificação sob demanda (`Notification::route('mail', $novoEmail)`) — é
 * justamente a prova de que o titular tem acesso à caixa nova. Não entra no
 * canal database: o código não deve aparecer na lista de notificações do app.
 */
class EmailChangeCode extends Notification
{
    use Queueable;

    public function __construct(
        public string $code,
        public string $name,
        public int $expiresInMinutes = 15,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Código para confirmar seu novo e-mail')
            ->greeting("Olá, {$this->name}!")
            ->line('Recebemos um pedido para usar este e-mail na sua conta do Super Personal.')
            ->line('Confirme com o código abaixo:')
            ->line("**{$this->code}**")
            ->line("O código vale por {$this->expiresInMinutes} minutos.")
            ->line('Se não foi você que pediu, ignore esta mensagem: seu e-mail atual continua valendo.');
    }
}
