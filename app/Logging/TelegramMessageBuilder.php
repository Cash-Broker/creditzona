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
                '%s | %s | %s',
                $this->sanitizeText($this->appName),
                $this->sanitizeText($this->environment),
                $record->level->getName(),
            ),
            'Съобщение: '.$this->sanitizeText($record->message),
        ];

        $exception = $this->extractException($record->context);

        if ($exception !== null) {
            $lines[] = 'Изключение: '.$exception::class;
            $lines[] = 'Грешка: '.$this->sanitizeText($exception->getMessage());
            $lines[] = 'Файл: '.$exception->getFile().':'.$exception->getLine();
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
            $lines[] = 'Контекст:';
            $lines[] = $this->encodeJson($context);
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
                'Заявка: %s %s',
                $request->method(),
                $request->path() === '/' ? '/' : '/'.ltrim($request->path(), '/'),
            ),
        ];

        if (filled($request->route()?->getName())) {
            $lines[] = 'Route: '.$request->route()?->getName();
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

        $lines = ['Потребител ID: '.$user->getKey()];

        if (filled(data_get($user, 'role'))) {
            $lines[] = 'Роля: '.$this->sanitizeText((string) data_get($user, 'role'));
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
}
