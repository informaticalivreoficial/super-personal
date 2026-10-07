<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailable;
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
        return new Envelope(
            subject: '🆘 Nova solicitação de suporte',  
            from: new Address(env('MAIL_FROM_ADDRESS'), config('app.name')), // Remetente
            to: [new Address(config('app.desenvolvedor_email'), config('app.desenvolvedor_nome'))], // Destinatário                
            replyTo: [
                new Address(env('MAIL_FROM_ADDRESS'), config('app.name')),
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.support',
            with:[
                'Cliente' => config('app.name'),
                'nome' => auth()->user()->name,
                'email' => env('MAIL_FROM_ADDRESS'),
                'mensagem' => $this->messageText
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
