<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SupportRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $messageText
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $user = auth()->user();

        return new Envelope(
            subject: '🆘 Nova solicitação de suporte',
            // Remetente sempre no domínio verificado (Mailtrap recusa
            // endereços fora dele). As respostas vão para quem pediu suporte.
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            to: [new Address(config('app.desenvolvedor_email'), config('app.desenvolvedor'))],
            replyTo: $user
                ? [new Address($user->email, $user->name)]
                : [new Address(config('mail.from.address'), config('mail.from.name'))],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $user = auth()->user();

        return new Content(
            markdown: 'emails.support',
            with: [
                'Cliente' => config('app.name'),
                'nome' => $user->name ?? '-',
                'email' => $user->email ?? config('mail.from.address'),
                'mensagem' => $this->messageText,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
