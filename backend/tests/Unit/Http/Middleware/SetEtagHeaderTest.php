<?php

namespace Tests\Unit\Http\Middleware;

use HiEvents\Http\Middleware\SetEtagHeader;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class SetEtagHeaderTest extends TestCase
{
    public function testSetsEtagHeaderOnSuccessfulGetResponse(): void
    {
        $middleware = new SetEtagHeader();
        $request = Request::create('/public/events/1', 'GET');
        $response = new Response('{"data":[]}');

        $result = $middleware->handle($request, fn () => $response);

        $this->assertTrue($result->headers->has('ETag'));
        $this->assertStringStartsWith('"', $result->headers->get('ETag'));
    }

    public function testReturnsNotModifiedWhenEtagMatches(): void
    {
        $middleware = new SetEtagHeader();
        $body = '{"data":[]}';
        $etag = '"' . hash('sha256', $body) . '"';
        $request = Request::create('/public/events/1', 'GET');
        $request->headers->set('If-None-Match', $etag);

        $result = $middleware->handle($request, fn () => new Response($body));

        $this->assertSame(Response::HTTP_NOT_MODIFIED, $result->getStatusCode());
        $this->assertSame('', $result->getContent());
    }

    public function testSkipsNonGetRequests(): void
    {
        $middleware = new SetEtagHeader();
        $request = Request::create('/public/events/1/order', 'POST');
        $response = new Response('{}', Response::HTTP_CREATED);

        $result = $middleware->handle($request, fn () => $response);

        $this->assertFalse($result->headers->has('ETag'));
    }
}
