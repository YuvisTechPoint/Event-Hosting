<?php

namespace HiEvents\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetEtagHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$request->isMethod('GET') || !$response->isSuccessful()) {
            return $response;
        }

        $content = $response->getContent();
        if ($content === false || $content === '') {
            return $response;
        }

        $etag = '"' . hash('sha256', $content) . '"';
        $response->headers->set('ETag', $etag);

        if ($request->headers->get('If-None-Match') === $etag) {
            $response->setStatusCode(Response::HTTP_NOT_MODIFIED);
            $response->setContent(null);

            return $response;
        }

        return $response;
    }
}
