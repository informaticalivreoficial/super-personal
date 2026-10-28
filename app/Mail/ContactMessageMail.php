<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Mensagem recebida pela página pública de atendimento.
 *
 * O `replyTo` aponta para quem escreveu, então basta responder o e-mail para
 * falar direto com o visitante (o remetente fica no domínio verificado).
 */
class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{name: string, email: string, phone?: ?string, subject?: ?string, message: string}  $data
     */
    public function __construct(public array $data) {}

    public function envelope(): Envelope
    {
        $subject = trim((string) ($this->data['subject'] ?? ''));

        return new Envelope(
            subject: 'Mensagem do site'.($subject !== '' ? ': '.$subject : ''),
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            replyTo: [new Address($this->data['email'], $this->data['name'])],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-message',
            with: ['data' => $this->data],
        );
    }
}
