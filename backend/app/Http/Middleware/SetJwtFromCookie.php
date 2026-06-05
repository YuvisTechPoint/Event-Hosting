<?php

namespace HiEvents\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows JWT auth on routes (e.g. broadcasting/auth) that receive the token via httpOnly cookie.
 */
class SetJwtFromCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->bearerToken() && $request->cookie('token')) {
            $request->headers->set('Authorization', 'Bearer ' . $request->cookie('token'));
        }

        return $next($request);
    }
}
