<?php

namespace Tests\Feature;

use App\Mail\ContactMessageReplyMail;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Models\User;
use App\Services\ContactMessageService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactMessageReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_operator_can_reply_and_email_is_queued(): void
    {
        Mail::fake();

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'name' => 'Анна Петрова',
            'email' => 'anna@creditzona.test',
        ]);

        $contactMessage = ContactMessage::query()->create($this->contactMessageData([
            'assigned_user_id' => $operator->id,
        ]));

        $reply = app(ContactMessageService::class)->reply(
            $contactMessage,
            $operator,
            "Здравейте,\n\nполучихме запитването Ви.\n\nПоздрави,\nАнна",
        );

        $this->assertInstanceOf(ContactMessageReply::class, $reply);
        $this->assertSame($contactMessage->id, $reply->contact_message_id);
        $this->assertSame($operator->id, $reply->sender_user_id);
        $this->assertSame('anna@creditzona.test', $reply->from_email);
        $this->assertSame('ivan@example.com', $reply->to_email);
        $this->assertStringContainsString('Re: Запитване от Иван Иванов', $reply->subject);
        $this->assertNotNull($reply->message_id);
        $this->assertNull($reply->in_reply_to);
        $this->assertNotNull($reply->sent_at);

        Mail::assertQueued(ContactMessageReplyMail::class, function (ContactMessageReplyMail $mail) use ($contactMessage, $operator, $reply): bool {
            return $mail->hasTo($contactMessage->email)
                && $mail->subjectLine === $reply->subject
                && $mail->messageId === $reply->message_id
                && $mail->sender->is($operator)
                && $mail->inReplyTo === null;
        });
    }

    public function test_subsequent_reply_chains_in_reply_to_and_references(): void
    {
        Mail::fake();

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $contactMessage = ContactMessage::query()->create($this->contactMessageData([
            'assigned_user_id' => $operator->id,
        ]));

        $service = app(ContactMessageService::class);
        $first = $service->reply($contactMessage, $operator, 'Първи отговор.');
        $second = $service->reply($contactMessage->refresh(), $operator, 'Втори отговор.');

        $this->assertSame($first->message_id, $second->in_reply_to);

        Mail::assertQueued(ContactMessageReplyMail::class, function (ContactMessageReplyMail $mail) use ($first): bool {
            return $mail->inReplyTo === $first->message_id
                && in_array($first->message_id, $mail->referenceMessageIds, true);
        });
    }

    public function test_reply_throws_when_actor_is_not_assigned_operator(): void
    {
        Mail::fake();

        $assignedOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $otherOperator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $contactMessage = ContactMessage::query()->create($this->contactMessageData([
            'assigned_user_id' => $assignedOperator->id,
        ]));

        $this->expectException(AuthorizationException::class);

        try {
            app(ContactMessageService::class)->reply($contactMessage, $otherOperator, 'Hi');
        } finally {
            Mail::assertNothingQueued();
            $this->assertSame(0, ContactMessageReply::query()->count());
        }
    }

    public function test_admin_cannot_reply_unless_also_assigned(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $contactMessage = ContactMessage::query()->create($this->contactMessageData([
            'assigned_user_id' => $operator->id,
        ]));

        $this->expectException(AuthorizationException::class);

        try {
            app(ContactMessageService::class)->reply($contactMessage, $admin, 'Hi');
        } finally {
            Mail::assertNothingQueued();
        }
    }

    public function test_reply_throws_when_message_has_no_email(): void
    {
        Mail::fake();

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $contactMessage = ContactMessage::query()->create($this->contactMessageData([
            'assigned_user_id' => $operator->id,
            'email' => '',
        ]));

        $this->expectException(DomainException::class);

        try {
            app(ContactMessageService::class)->reply($contactMessage, $operator, 'Hi');
        } finally {
            Mail::assertNothingQueued();
            $this->assertSame(0, ContactMessageReply::query()->count());
        }
    }

    public function test_reply_throws_when_body_is_empty(): void
    {
        Mail::fake();

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $contactMessage = ContactMessage::query()->create($this->contactMessageData([
            'assigned_user_id' => $operator->id,
        ]));

        $this->expectException(DomainException::class);

        try {
            app(ContactMessageService::class)->reply($contactMessage, $operator, '   ');
        } finally {
            Mail::assertNothingQueued();
        }
    }

    public function test_replies_relationship_returns_replies_in_chronological_order(): void
    {
        Mail::fake();

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $contactMessage = ContactMessage::query()->create($this->contactMessageData([
            'assigned_user_id' => $operator->id,
        ]));

        $service = app(ContactMessageService::class);
        $service->reply($contactMessage, $operator, 'Първи отговор.');
        $service->reply($contactMessage->refresh(), $operator, 'Втори отговор.');

        $bodies = $contactMessage->fresh()->replies->pluck('body')->all();

        $this->assertSame(['Първи отговор.', 'Втори отговор.'], $bodies);
    }

    /**
     * @return array<string, mixed>
     */
    private function contactMessageData(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Иван Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'message' => 'Тестово съобщение.',
            'assigned_user_id' => null,
        ], $overrides);
    }
}
