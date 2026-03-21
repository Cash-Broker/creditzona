<?php

namespace App\Logging;

use Illuminate\Support\Facades\Http;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class TelegramHandler extends AbstractProcessingHandler
{
    public function __construct(
        private readonly string $botToken,
        private readonly string $chatId,
        private readonly TelegramMessageBuilder $messageBuilder,
        private readonly ?string $messageThreadId = null,
        int|string|Level $level = Level::Error,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        $payload = [
            'chat_id' => $this->chatId,
            'text' => $this->messageBuilder->build($record),
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if (filled($this->messageThreadId)) {
            $payload['message_thread_id'] = $this->messageThreadId;
        }

        try {
            Http::asForm()
                ->timeout(5)
                ->retry(1, 200)
                ->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", $payload);
        } catch (\Throwable) {
            // Never break the main request because Telegram is unavailable.
        }
    }
}
