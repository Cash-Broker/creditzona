<?php

namespace Tests\Feature;

use App\Mail\ContactMessageReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactMessageSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_message_submission_stores_message_and_sends_mail_without_queueing(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/contact-messages', $this->validPayload([
            'phone' => '+359 888 123 456',
        ]));

        $response
            ->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Съобщението беше изпратено успешно.',
            ]);

        $this->assertDatabaseHas('contact_messages', [
            'full_name' => 'Иван Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'message' => 'Търся консултация за рефинансиране на текущ кредит.',
        ]);

        Mail::assertSent(ContactMessageReceived::class, function (ContactMessageReceived $mail): bool {
            return $mail->contactMessage->full_name === 'Иван Иванов'
                && $mail->contactMessage->phone === '0888123456';
        });
        Mail::assertNotQueued(ContactMessageReceived::class);
        Mail::assertNothingQueued();
    }

    public function test_contact_message_submission_rejects_honeypot(): void
    {
        $response = $this->postJson('/api/contact-messages', $this->validPayload([
            'website' => 'spam-bot',
        ]));

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['website']);
    }

    public function test_contact_message_submission_is_rate_limited(): void
    {
        $client = $this->withServerVariables([
            'REMOTE_ADDR' => '10.20.30.40',
            'HTTP_USER_AGENT' => 'ContactThrottleTest',
        ]);

        foreach (range(1, 5) as $index) {
            $client->postJson('/api/contact-messages', $this->validPayload([
                'email' => "ivan{$index}@example.com",
            ]))->assertCreated();
        }

        $client->postJson('/api/contact-messages', $this->validPayload([
            'email' => 'ivan6@example.com',
        ]))
            ->assertStatus(429)
            ->assertJsonPath('message', 'Изпращате твърде често. Моля, опитайте отново след малко.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Иван Иванов',
            'phone' => '0888123456',
            'email' => 'ivan@example.com',
            'message' => 'Търся консултация за рефинансиране на текущ кредит.',
            'website' => '',
            'form_started_at' => now()->subSeconds(5)->getTimestampMs(),
        ], $overrides);
    }
}
