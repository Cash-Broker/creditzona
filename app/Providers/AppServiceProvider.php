<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('lead-submissions', function (Request $request): Limit {
            return Limit::perMinutes(10, 5)
                ->by($this->throttleKey($request))
                ->response(fn (Request $request, array $headers) => $this->buildThrottleResponse($request, $headers));
        });

        RateLimiter::for('contact-messages', function (Request $request): Limit {
            return Limit::perMinutes(10, 5)
                ->by($this->throttleKey($request))
                ->response(fn (Request $request, array $headers) => $this->buildThrottleResponse($request, $headers));
        });
    }

    private function throttleKey(Request $request): string
    {
        return sha1(implode('|', [
            $request->route()?->getName() ?? $request->path(),
            $request->ip(),
            (string) $request->userAgent(),
        ]));
    }

    private function buildThrottleResponse(Request $request, array $headers)
    {
        $message = 'Изпращате твърде често. Моля, опитайте отново след малко.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ], 429, $headers);
        }

        return response($message, 429, $headers);
    }
}
