<?php

namespace HiEvents\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeRequestInput
{
    private const SKIP_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $request->merge($this->sanitizeArray($request->all()));

        return $next($request);
    }

    private function sanitizeArray(array $data, ?string $parentKey = null): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->sanitizeArray($value, (string) $key);
                continue;
            }

            if (!is_string($value) || in_array($key, self::SKIP_KEYS, true)) {
                continue;
            }

            $data[$key] = $this->sanitizeString($value);
        }

        return $data;
    }

    private function sanitizeString(string $value): string
    {
        $value = str_replace("\0", '', $value);

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;
    }
}
