<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Common;

use HiEvents\Http\Actions\BaseAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class HealthAction extends BaseAction
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
        ];

        $healthy = !in_array(false, $checks, true);

        return $this->jsonResponse([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
        ], $healthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function checkRedis(): bool
    {
        if (!$this->usesRedis()) {
            return true;
        }

        try {
            return Redis::connection()->ping() !== false;
        } catch (Throwable) {
            return false;
        }
    }

    private function usesRedis(): bool
    {
        return in_array('redis', [
            config('queue.default'),
            config('cache.default'),
            config('session.driver'),
        ], true);
    }
}
