<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class LeadSubmittedConfirmation extends Mailable
{
    public function __construct(
        public Lead $lead
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Получихме Вашата заявка в CreditZona',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lead-submitted-confirmation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
