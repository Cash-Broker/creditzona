<?php

namespace App\Mail;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class LeadEmail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Lead $lead,
        public User $sender,
        public string $body,
        public string $subjectLine,
        public string $messageId,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->sender->email, $this->sender->name),
            replyTo: [new Address($this->sender->email, $this->sender->name)],
            subject: $this->subjectLine,
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            messageId: $this->messageId,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lead-email',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
