<?php

namespace App\Support\Forms;

use Illuminate\Support\Carbon;

/**
 * Server-issued, HMAC-signed proof of when a public form was rendered.
 *
 * The token replaces the previous client-supplied `form_started_at` timestamp,
 * which a bot could forge by simply posting `Date.now() - 4000`. Here the
 * timestamp is signed with the application key at render time, so the client
 * cannot fabricate a value that survives verification. We then enforce a
 * minimum dwell time (a human needs more than a few seconds to fill the form)
 * and reject absurdly stale tokens.
 */
class FormTimingToken
{
    /**
     * Minimum seconds between form render and submit. Anything faster is a bot.
     */
    private const MINIMUM_FILL_SECONDS = 3;

    /**
     * Upper bound only guards against replay of very old tokens; the real
     * anti-replay control is Cloudflare Turnstile, so this is deliberately
     * generous to avoid rejecting genuine users who keep a tab open.
     */
    private const MAXIMUM_AGE_SECONDS = 86400;

    public static function issue(?int $issuedAtMs = null): string
    {
        $issuedAt = $issuedAtMs ?? (int) Carbon::now()->getTimestampMs();

        return $issuedAt.'.'.self::sign($issuedAt);
    }

    public static function isValid(?string $token): bool
    {
        if (! is_string($token) || $token === '') {
            return false;
        }

        $segments = explode('.', $token, 2);

        if (count($segments) !== 2) {
            return false;
        }

        [$issuedAtRaw, $signature] = $segments;

        if (! ctype_digit($issuedAtRaw)) {
            return false;
        }

        $issuedAt = (int) $issuedAtRaw;

        if (! hash_equals(self::sign($issuedAt), $signature)) {
            return false;
        }

        $ageSeconds = (Carbon::now()->getTimestampMs() - $issuedAt) / 1000;

        if ($ageSeconds < self::MINIMUM_FILL_SECONDS) {
            return false;
        }

        if ($ageSeconds > self::MAXIMUM_AGE_SECONDS) {
            return false;
        }

        return true;
    }

    private static function sign(int $issuedAtMs): string
    {
        return hash_hmac('sha256', (string) $issuedAtMs, self::secret());
    }

    private static function secret(): string
    {
        return (string) config('app.key');
    }
}
