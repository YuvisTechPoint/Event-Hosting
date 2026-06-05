<?php

namespace Tests\Unit\Http\Middleware;

use HiEvents\Http\Middleware\SanitizeRequestInput;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class SanitizeRequestInputTest extends TestCase
{
    public function testRemovesNullBytesAndControlCharacters(): void
    {
        $middleware = new SanitizeRequestInput();
        $request = Request::create('/api/test', 'POST', [
            'name' => "hello\x00world\x07",
            'password' => "keep\x00me",
        ]);

        $middleware->handle($request, fn () => new Response('ok'));

        $this->assertSame('helloworld', $request->input('name'));
        $this->assertSame("keep\x00me", $request->input('password'));
    }
}
