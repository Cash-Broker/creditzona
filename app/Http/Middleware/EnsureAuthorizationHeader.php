<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        $hasOnRequest = $request->headers->has('Authorization');
        $resolved = $hasOnRequest ? $request->headers->get('Authorization') : $this->resolveAuthorization();

        Log::info('AuthHeader debug', [
            'url' => $request->fullUrl(),
            'has_on_request' => $hasOnRequest,
            'resolved_present' => $resolved !== null && $resolved !== '',
            'resolved_preview' => $resolved ? substr($resolved, 0, 30) . '...' : null,
            'server_HTTP_AUTHORIZATION' => isset($_SERVER['HTTP_AUTHORIZATION']) ? 'YES' : 'no',
            'server_REDIRECT_HTTP_AUTHORIZATION' => isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? 'YES' : 'no',
            'server_REDIRECT_REDIRECT_HTTP_AUTHORIZATION' => isset($_SERVER['REDIRECT_REDIRECT_HTTP_AUTHORIZATION']) ? 'YES' : 'no',
            'apache_request_headers_exists' => function_exists('apache_request_headers'),
            'getallheaders_exists' => function_exists('getallheaders'),
            'apache_authorization' => function_exists('apache_request_headers') ? (apache_request_headers()['Authorization'] ?? 'MISSING') : 'no_function',
            'getallheaders_authorization' => function_exists('getallheaders') ? (getallheaders()['Authorization'] ?? 'MISSING') : 'no_function',
            'auth_related_server_keys' => array_keys(array_filter($_SERVER, fn ($k) => stripos($k, 'auth') !== false, ARRAY_FILTER_USE_KEY)),
        ]);

        if (!$hasOnRequest && $resolved !== null && $resolved !== '') {
            $request->headers->set('Authorization', $resolved);
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
