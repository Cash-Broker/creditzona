<?php

namespace App\Mail;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class ContactMessageReplyMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<int, string>  $referenceMessageIds
     */
    public function __construct(
        public ContactMessage $contactMessage,
        public User $sender,
        public string $body,
        public string $subjectLine,
        public string $messageId,
        public ?string $inReplyTo = null,
        public array $referenceMessageIds = [],
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
        $text = [];

        if ($this->inReplyTo !== null && $this->inReplyTo !== '') {
            $text['In-Reply-To'] = '<'.$this->inReplyTo.'>';
        }

        if ($this->referenceMessageIds !== []) {
            $text['References'] = implode(' ', array_map(
                static fn (string $id): string => '<'.$id.'>',
                $this->referenceMessageIds,
            ));
        }

        return new Headers(
            messageId: $this->messageId,
            text: $text,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-message-reply',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
