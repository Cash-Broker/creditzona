<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactMessage $contactMessage
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = [];

        if (!empty($this->contactMessage->email)) {
            $replyTo[] = new Address(
                $this->contactMessage->email,
                $this->contactMessage->full_name
            );
        }

        $subject = $this->contactMessage->subject
            ? 'Ново запитване: ' . $this->contactMessage->subject
            : 'Ново съобщение от контактната форма';

        return new Envelope(
            subject: $subject,
            replyTo: $replyTo
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-message-received'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
