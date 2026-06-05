<?php

namespace Tests\Unit\Services\Infrastructure\Security;

use HiEvents\Services\Infrastructure\Security\AuthSecurityService;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Log;
use Psr\Log\NullLogger;
use Tests\TestCase;

class AuthSecurityServiceTest extends TestCase
{
    private AuthSecurityService $service;

    private Repository $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache = new Repository(new ArrayStore());
        $this->service = new AuthSecurityService($this->cache, new NullLogger());

        config([
            'security.auth.brute_force_base_seconds' => 2,
            'security.auth.brute_force_max_seconds' => 900,
            'security.auth.login_decay_minutes' => 15,
        ]);
    }

    public function testRecordFailedAttemptAppliesExponentialBackoff(): void
    {
        Log::shouldReceive('warning')->andReturnNull();

        $this->service->recordFailedAttempt('login', 'user@example.com|127.0.0.1', '127.0.0.1');

        $this->expectException(ThrottleRequestsException::class);

        $this->service->assertNotLockedOut('login', 'user@example.com|127.0.0.1', '127.0.0.1');
    }

    public function testSuccessfulAttemptClearsLockout(): void
    {
        Log::shouldReceive('warning')->andReturnNull();

        $identifier = 'user@example.com|127.0.0.1';
        $this->service->recordFailedAttempt('login', $identifier, '127.0.0.1');
        $this->service->recordSuccessfulAttempt('login', $identifier);

        $this->service->assertNotLockedOut('login', $identifier, '127.0.0.1');

        $this->assertTrue(true);
    }
}
