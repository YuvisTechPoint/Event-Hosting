<?php

namespace Tests\Unit\Http\Middleware;

use HiEvents\Http\Middleware\ValidateApiCsrfToken;
use HiEvents\Services\Infrastructure\Security\CsrfTokenService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class ValidateApiCsrfTokenTest extends TestCase
{
    public function testSkipsValidationWhenBearerTokenPresent(): void
    {
        $middleware = new ValidateApiCsrfToken(new CsrfTokenService());
        $request = Request::create('/api/events', 'POST');
        $request->headers->set('Authorization', 'Bearer test-token');
        $request->cookies->set('token', 'cookie-token');

        $result = $middleware->handle($request, fn () => new Response('ok', Response::HTTP_CREATED));

        $this->assertSame(Response::HTTP_CREATED, $result->getStatusCode());
    }

    public function testRejectsCookieAuthWithoutCsrfToken(): void
    {
        $middleware = new ValidateApiCsrfToken(new CsrfTokenService());
        $request = Request::create('/api/events', 'POST');
        $request->cookies->set('token', 'cookie-token');

        $result = $middleware->handle($request, fn () => new Response('ok', Response::HTTP_CREATED));

        $this->assertSame(Response::HTTP_FORBIDDEN, $result->getStatusCode());
    }

    public function testAcceptsMatchingCsrfTokenForCookieAuth(): void
    {
        $middleware = new ValidateApiCsrfToken(new CsrfTokenService());
        $request = Request::create('/api/events', 'POST');
        $request->cookies->set('token', 'cookie-token');
        $request->cookies->set('XSRF-TOKEN', 'csrf-token');
        $request->headers->set('X-XSRF-TOKEN', 'csrf-token');

        $result = $middleware->handle($request, fn () => new Response('ok', Response::HTTP_CREATED));

        $this->assertSame(Response::HTTP_CREATED, $result->getStatusCode());
    }
}
