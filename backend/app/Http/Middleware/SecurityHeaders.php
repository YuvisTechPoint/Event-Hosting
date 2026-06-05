<?php

namespace HiEvents\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', config('security.headers.frame_options', 'SAMEORIGIN'));
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', config('security.headers.referrer_policy', 'strict-origin-when-cross-origin'));
        $response->headers->set('Permissions-Policy', config('security.headers.permissions_policy', 'camera=(), microphone=(), geolocation=()'));
        $response->headers->set('X-XSS-Protection', '0');

        if ($request->secure() || app()->environment('production')) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=' . config('security.headers.hsts_max_age', 31536000) . '; includeSubDomains',
            );
        }

        return $response;
    }
}
