<?php

namespace HiEvents\Services\Infrastructure\Security;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class CsrfTokenService
{
    public function createCookie(): Cookie
    {
        $token = Str::random(40);

        return cookie(
            name: config('security.csrf.cookie_name', 'XSRF-TOKEN'),
            value: $token,
            minutes: config('security.csrf.token_ttl_minutes', 120),
            secure: true,
            httpOnly: false,
            sameSite: 'None',
        );
    }

    public function validate(Request $request): bool
    {
        $cookieName = config('security.csrf.cookie_name', 'XSRF-TOKEN');
        $headerName = config('security.csrf.header_name', 'X-XSRF-TOKEN');

        $cookieToken = $request->cookie($cookieName);
        $headerToken = $request->header($headerName);

        if (!$cookieToken || !$headerToken) {
            return false;
        }

        return hash_equals($cookieToken, $headerToken);
    }
}
