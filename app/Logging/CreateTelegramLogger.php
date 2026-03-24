<?php

namespace App\Logging;

use Monolog\Handler\NullHandler;
use Monolog\Level;
use Monolog\Logger;

class CreateTelegramLogger
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __invoke(array $config): Logger
    {
        $name = (string) ($config['name'] ?? 'telegram');
        $botToken = trim((string) ($config['bot_token'] ?? ''));
        $chatId = trim((string) ($config['chat_id'] ?? ''));
        $environment = (string) ($config['environment'] ?? config('app.env', 'production'));

        if ($environment !== 'production' || $botToken === '' || $chatId === '') {
            return new Logger($name, [new NullHandler]);
        }

        return new Logger($name, [
            new TelegramHandler(
                botToken: $botToken,
                chatId: $chatId,
                messageBuilder: new TelegramMessageBuilder(
                    appName: (string) ($config['app_name'] ?? config('app.name', 'Laravel')),
                    environment: $environment,
                ),
                messageThreadId: filled($config['message_thread_id'] ?? null)
                    ? (string) $config['message_thread_id']
                    : null,
                level: $this->resolveLevel($config['level'] ?? 'error'),
            ),
        ]);
    }

    private function resolveLevel(mixed $level): Level
    {
        if ($level instanceof Level) {
            return $level;
        }

        try {
            return Level::fromName(strtoupper((string) $level));
        } catch (\ValueError) {
            return Level::Error;
        }
    }
}
