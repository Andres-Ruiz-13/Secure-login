<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessage extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $userMessage,
        public ?string $phone = null,
        public ?string $subjectLine = null,
        public bool $isConfirmationToUser = false
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isConfirmationToUser
            ? 'Confirmación de recepción de tu mensaje - ' . config('app.name')
            : ($this->subjectLine ?? 'Nuevo mensaje de contacto desde el sitio web');

        return new Envelope(
            subject: $subject
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-message'
        );
    }
}
