<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictMarketingToDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isMarketing()) {
            return $next($request);
        }

        if ($request->routeIs('filament.admin.resources.*')) {
            return redirect()->route('filament.admin.pages.dashboard');
        }

        return $next($request);
    }
}
