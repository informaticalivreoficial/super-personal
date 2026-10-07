<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification as LaravelNotification;

/**
 * Base das notificações do Super Personal.
 * Arquitetura preparada: canal database hoje; push Android no futuro
 * basta acrescentar canais em via().
 */
abstract class BaseNotification extends LaravelNotification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(public array $data = []) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->data;
    }
}
