<?php

namespace Tests\Feature;

use App\Logging\CreateTelegramLogger;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramLoggingTest extends TestCase
{
    public function test_telegram_logger_sends_error_payload_with_sanitized_context(): void
    {
        Http::fake();

        $logger = (new CreateTelegramLogger)([
            'name' => 'telegram',
            'bot_token' => 'test-bot-token',
            'chat_id' => '123456',
            'message_thread_id' => '77',
            'app_name' => 'CreditZona',
            'environment' => 'production',
            'level' => 'error',
        ]);

        $logger->error('Lead submission failed for ivan@example.com', [
            'lead_id' => 42,
            'egn' => '1234567890',
            'phone' => '0899000000',
            'email' => 'ivan@example.com',
            'documents' => ['lead-documents/1/application.pdf'],
            'message' => 'Свободен текст от клиента',
        ]);

        Http::assertSent(function ($request): bool {
            $text = (string) $request['text'];

            return $request->url() === 'https://api.telegram.org/bottest-bot-token/sendMessage'
                && $request['chat_id'] === '123456'
                && $request['message_thread_id'] === '77'
                && str_contains($text, 'CreditZona | production | ERROR')
                && str_contains($text, 'Lead submission failed for [скрит имейл]')
                && str_contains($text, '"lead_id": 42')
                && str_contains($text, '"egn": "[скрити лични данни]"')
                && str_contains($text, '"phone": "[скрити лични данни]"')
                && str_contains($text, '"email": "[скрити лични данни]"')
                && str_contains($text, '"documents": "[скрити лични данни]"')
                && str_contains($text, '"message": "[скрити лични данни]"')
                && ! str_contains($text, '1234567890')
                && ! str_contains($text, '0899000000')
                && ! str_contains($text, 'ivan@example.com')
                && ! str_contains($text, 'application.pdf');
        });
    }

    public function test_telegram_logger_gracefully_becomes_noop_without_credentials(): void
    {
        Http::fake();

        $logger = (new CreateTelegramLogger)([
            'name' => 'telegram',
            'bot_token' => '',
            'chat_id' => '',
            'level' => 'error',
        ]);

        $logger->error('This should stay local only.');

        Http::assertNothingSent();
    }
}
