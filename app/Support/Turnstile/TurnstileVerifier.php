<?php

namespace App\Support\Turnstile;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Verifies Cloudflare Turnstile tokens server-side.
 *
 * When no secret is configured the verifier is disabled and treats every
 * submission as valid, so the feature can be rolled out by simply adding the
 * keys to the environment (mirroring how analytics stays off until configured).
 * When a secret IS present, verification is fail-closed: a missing token, a
 * non-2xx response, a network error, or a `success: false` body all reject the
 * submission, so spam can never slip through on a verification hiccup.
 */
class TurnstileVerifier
{
    public function isEnabled(): bool
    {
        return $this->secret() !== '';
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (! $this->isEnabled()) {
            return true;
        }

        if ($token === null || $token === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post($this->verifyUrl(), array_filter([
                    'secret' => $this->secret(),
                    'response' => $token,
                    'remoteip' => $ip,
                ], static fn (?string $value): bool => $value !== null && $value !== ''));
        } catch (Throwable $exception) {
            Log::warning('Turnstile verification request failed.', [
                'error' => $exception->getMessage(),
            ]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('Turnstile verification returned an unexpected status.', [
                'status' => $response->status(),
            ]);

            return false;
        }

        return (bool) $response->json('success', false);
    }

    private function secret(): string
    {
        return trim((string) config('services.turnstile.secret'));
    }

    private function verifyUrl(): string
    {
        $url = trim((string) config('services.turnstile.verify_url'));

        return $url !== ''
            ? $url
            : 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    }
}
