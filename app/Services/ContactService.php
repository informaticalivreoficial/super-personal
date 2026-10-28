<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Mail\ContactMessageMail;
use App\Models\Config;
use App\Models\User;
use App\Notifications\ContactMessageReceived;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Mensagens da página pública de atendimento.
 *
 * Caminho único de escrita: o formulário não fala com e-mail, banco nem
 * Telegram diretamente. Cada canal é independente — a falha de um não impede
 * os outros nem expõe erro ao visitante (a página sempre confirma o envio e a
 * equipe continua sendo avisada pelo canal que estiver de pé).
 */
class ContactService
{
    public function __construct(private TelegramService $telegram) {}

    /**
     * @param  array{name: string, email: string, phone?: ?string, subject?: ?string, message: string}  $data
     */
    public function send(array $data): void
    {
        $this->sendEmail($data);
        $this->notifyAdmins($data);
        $this->sendTelegram($data);
    }

    /**
     * Destinatário do e-mail: `MAIL_CONTACT_TO`, senão o e-mail cadastrado nas
     * Configurações do site, senão o próprio remetente do sistema.
     */
    public function recipient(): string
    {
        $configured = trim((string) config('mail.contact_to'));

        if ($configured !== '') {
            return $configured;
        }

        $fromSettings = trim((string) Config::query()->value('email'));

        return $fromSettings !== '' ? $fromSettings : (string) config('mail.from.address');
    }

    private function sendEmail(array $data): void
    {
        try {
            Mail::to($this->recipient())->send(new ContactMessageMail($data));
        } catch (Throwable $exception) {
            // SMTP fora do ar não pode derrubar o formulário; o registro no
            // banco e o Telegram continuam valendo.
            report($exception);
            Log::error('Falha ao enviar o e-mail do atendimento.', [
                'email' => $data['email'],
            ]);
        }
    }

    private function notifyAdmins(array $data): void
    {
        try {
            $admins = User::query()
                ->where('role', UserRole::ADMIN->value)
                ->get();

            if ($admins->isEmpty()) {
                return;
            }

            Notification::send($admins, ContactMessageReceived::fromRequest($data));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function sendTelegram(array $data): void
    {
        $lines = [
            '🆘 <b>Nova mensagem de atendimento</b>',
            '',
            '<b>Nome:</b> '.e($data['name']),
            '<b>E-mail:</b> '.e($data['email']),
        ];

        if (! empty($data['phone'])) {
            $lines[] = '<b>Telefone:</b> '.e($data['phone']);
        }

        if (! empty($data['subject'])) {
            $lines[] = '<b>Assunto:</b> '.e($data['subject']);
        }

        $lines[] = '';
        $lines[] = e($data['message']);

        $this->telegram->send(implode("\n", $lines));
    }
}
