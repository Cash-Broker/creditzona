<?php

namespace App\Logging;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Monolog\LogRecord;
use Throwable;

class TelegramMessageBuilder
{
    /**
     * @var array<int, string>
     */
    private const SENSITIVE_SECRET_KEYS = [
        'password',
        'token',
        'secret',
        'authorization',
        'cookie',
        'remember_token',
        'api_key',
    ];

    /**
     * @var array<int, string>
     */
    private const SENSITIVE_PERSONAL_KEYS = [
        'egn',
        'email',
        'phone',
        'mobile',
        'tel',
        'full_name',
        'first_name',
        'middle_name',
        'last_name',
        'message',
        'internal_notes',
        'documents',
        'document',
        'file',
        'city',
        'workplace',
        'job_title',
        'salary_bank',
        'credit_bank',
        'property_location',
    ];

    public function __construct(
        private readonly string $appName,
        private readonly string $environment,
    ) {}

    public function build(LogRecord $record): string
    {
        $lines = [
            sprintf(
                '<b>%s</b> | <b>%s</b>',
                $this->escape($this->appName),
                $record->level->getName(),
            ),
            sprintf('<b>Среда:</b> <code>%s</code>', $this->escape($this->environment)),
            sprintf(
                '<b>Съобщение:</b> %s',
                $this->escape($this->sanitizeText($record->message)),
            ),
        ];

        $exception = $this->extractException($record->context);

        if ($exception !== null) {
            $lines[] = sprintf(
                '<b>Изключение:</b> <code>%s</code>',
                $this->escape($exception::class),
            );
            $lines[] = sprintf(
                '<b>Детайл:</b> %s',
                $this->escape($this->sanitizeText($exception->getMessage())),
            );
            $lines[] = sprintf(
                '<b>Файл:</b> <code>%s:%s</code>',
                $this->escape($exception->getFile()),
                $exception->getLine(),
            );
        }

        if ($requestSummary = $this->buildRequestSummary()) {
            $lines = [...$lines, ...$requestSummary];
        }

        if ($userSummary = $this->buildUserSummary()) {
            $lines = [...$lines, ...$userSummary];
        }

        $context = $this->sanitizeContext($record->context);
        unset($context['exception']);

        if ($context !== []) {
            $lines[] = '<b>Контекст:</b>';
            $lines[] = '<pre>'.$this->escape($this->encodeJson($context)).'</pre>';
        }

        return Str::limit(implode("\n", $lines), 3900, '...');
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function extractException(array $context): ?Throwable
    {
        $exception = $context['exception'] ?? null;

        return $exception instanceof Throwable ? $exception : null;
    }

    /**
     * @return array<int, string>
     */
    private function buildRequestSummary(): array
    {
        if (! app()->bound('request')) {
            return [];
        }

        $request = request();

        if (! $request) {
            return [];
        }

        $lines = [
            sprintf(
                '<b>Заявка:</b> <code>%s %s</code>',
                $this->escape($request->method()),
                $this->escape($request->path() === '/' ? '/' : '/'.ltrim($request->path(), '/')),
            ),
        ];

        if (filled($request->route()?->getName())) {
            $lines[] = sprintf(
                '<b>Route:</b> <code>%s</code>',
                $this->escape((string) $request->route()?->getName()),
            );
        }

        return $lines;
    }

    /**
     * @return array<int, string>
     */
    private function buildUserSummary(): array
    {
        if (! app()->bound('auth')) {
            return [];
        }

        $user = auth()->user();

        if (! $user instanceof Model) {
            return [];
        }

        $lines = [
            sprintf('<b>Потребител ID:</b> <code>%s</code>', $user->getKey()),
        ];

        if (filled(data_get($user, 'role'))) {
            $lines[] = sprintf(
                '<b>Роля:</b> <code>%s</code>',
                $this->escape((string) data_get($user, 'role')),
            );
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function sanitizeContext(array $context): array
    {
        $sanitized = [];

        foreach ($context as $key => $value) {
            $sanitized[$key] = $this->sanitizeValue((string) $key, $value);
        }

        return $sanitized;
    }

    private function sanitizeValue(string $key, mixed $value): mixed
    {
        $normalizedKey = Str::lower($key);

        if ($placeholder = $this->sensitivePlaceholder($normalizedKey)) {
            return $placeholder;
        }

        if ($value instanceof Throwable) {
            return $value::class;
        }

        if ($value instanceof Model) {
            return sprintf('%s#%s', class_basename($value), (string) $value->getKey());
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $nestedKey => $nestedValue) {
                $childKey = is_string($nestedKey) ? $nestedKey : $normalizedKey;
                $sanitized[$nestedKey] = $this->sanitizeValue($childKey, $nestedValue);
            }

            return $sanitized;
        }

        if (is_object($value)) {
            return $value::class;
        }

        if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
            return $value;
        }

        return $this->sanitizeText((string) $value);
    }

    private function sensitivePlaceholder(string $normalizedKey): ?string
    {
        foreach (self::SENSITIVE_SECRET_KEYS as $needle) {
            if (str_contains($normalizedKey, $needle)) {
                return '[скрита стойност]';
            }
        }

        foreach (self::SENSITIVE_PERSONAL_KEYS as $needle) {
            if (str_contains($normalizedKey, $needle)) {
                return '[скрити лични данни]';
            }
        }

        return null;
    }

    private function sanitizeText(string $value): string
    {
        $value = preg_replace('/[^\S\r\n]+/u', ' ', trim($value)) ?: '';
        $value = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', '[скрит имейл]', $value) ?: $value;

        return $value;
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function encodeJson(array $value): string
    {
        return (string) json_encode(
            $value,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
