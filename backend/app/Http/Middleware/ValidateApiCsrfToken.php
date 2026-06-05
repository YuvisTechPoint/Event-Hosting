<?php

namespace HiEvents\Http\Middleware;

use Closure;
use HiEvents\Services\Infrastructure\Security\CsrfTokenService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiCsrfToken
{
    private const STATEFUL_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(
        private readonly CsrfTokenService $csrfTokenService,
    )
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->requiresCsrfValidation($request)) {
            return $next($request);
        }

        if (!$this->csrfTokenService->validate($request)) {
            return response()->json([
                'message' => __('CSRF token mismatch.'),
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

    private function requiresCsrfValidation(Request $request): bool
    {
        if (!in_array($request->method(), self::STATEFUL_METHODS, true)) {
            return false;
        }

        if ($request->bearerToken()) {
            return false;
        }

        if (!$request->cookie('token')) {
            return false;
        }

        return true;
    }
}
