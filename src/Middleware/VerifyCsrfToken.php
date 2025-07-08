<?php

namespace Velto\Core\Middleware;

use Closure;

class VerifyCsrfToken
{
    public function handle($request, Closure $next)
    {

        $method = $request->method();

        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            
            $token = $request->input('_token') ?? '';
            if (!csrf_verify($token)) {
                abort(419, 'CSRF token mismatch.');
            }
        }

        return $next($request);
    }
}