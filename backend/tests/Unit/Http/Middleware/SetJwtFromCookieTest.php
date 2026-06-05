<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use HiEvents\Http\Middleware\SetJwtFromCookie;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class SetJwtFromCookieTest extends TestCase
{
    public function testSetsBearerTokenFromCookieWhenHeaderMissing(): void
    {
        $middleware = new SetJwtFromCookie();
        $request = Request::create('/api/events', 'GET');
        $request->cookies->set('token', 'jwt-from-cookie');

        $middleware->handle($request, fn () => new Response('ok'));

        $this->assertSame('Bearer jwt-from-cookie', $request->headers->get('Authorization'));
    }

    public function testDoesNotOverrideExistingBearerToken(): void
    {
        $middleware = new SetJwtFromCookie();
        $request = Request::create('/api/events', 'GET');
        $request->headers->set('Authorization', 'Bearer existing-token');
        $request->cookies->set('token', 'jwt-from-cookie');

        $middleware->handle($request, fn () => new Response('ok'));

        $this->assertSame('Bearer existing-token', $request->headers->get('Authorization'));
    }
}
