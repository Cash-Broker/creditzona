<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetFilamentLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale('bg');
        config([
            'app.locale' => 'bg',
            'app.fallback_locale' => 'bg',
        ]);

        Carbon::setLocale('bg');

        return $next($request);
    }
}
