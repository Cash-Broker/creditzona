<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Some PHP-FPM / LSAPI hosts (e.g. SuperHosting) don't forward the HTTP
 * Authorization header to PHP via the standard $_SERVER['HTTP_AUTHORIZATION']
 * key, even with CGIPassAuth On / mod_rewrite RewriteRule. This middleware
 * looks for the header in every common location and re-attaches it to the
 * Laravel Request so Sanctum's TokenGuard can read it.
 */
class EnsureAuthorizationHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->headers->has('Authorization')) {
            return $next($request);
        }

        $authorization = $this->resolveAuthorization();

        if ($authorization !== null && $authorization !== '') {
            $request->headers->set('Authorization', $authorization);
        }

        return $next($request);
    }

    private function resolveAuthorization(): ?string
    {
        foreach ([
            'HTTP_AUTHORIZATION',
            'REDIRECT_HTTP_AUTHORIZATION',
            'REDIRECT_REDIRECT_HTTP_AUTHORIZATION',
            'PHP_AUTH_USER_AUTHORIZATION',
        ] as $key) {
            if (!empty($_SERVER[$key])) {
                return $_SERVER[$key];
            }
        }

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            foreach ($headers as $name => $value) {
                if (strcasecmp($name, 'Authorization') === 0 && !empty($value)) {
                    return $value;
                }
            }
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $name => $value) {
                    if (strcasecmp($name, 'Authorization') === 0 && !empty($value)) {
                        return $value;
                    }
                }
            }
        }

        return null;
    }
}
