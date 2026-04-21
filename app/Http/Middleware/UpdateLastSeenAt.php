<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeenAt
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $request->user()->updateQuietly(['last_seen_at' => now()]);
        }

        return $next($request);
    }
}
