<?php

namespace Tests\Unit\Http\Middleware;

use HiEvents\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function testSetsSecurityHeadersOnResponse(): void
    {
        $middleware = new SecurityHeaders();
        $request = Request::create('/api/health', 'GET');

        $result = $middleware->handle($request, fn () => new Response('ok'));

        $this->assertSame('SAMEORIGIN', $result->headers->get('X-Frame-Options'));
        $this->assertSame('nosniff', $result->headers->get('X-Content-Type-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $result->headers->get('Referrer-Policy'));
    }
}
