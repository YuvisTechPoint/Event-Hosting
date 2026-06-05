<?php

declare(strict_types=1);

namespace HiEvents\Services\Infrastructure\Broadcasting;

use HiEvents\Exceptions\CannotCheckInException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

readonly class AttendeeCheckInLockService
{
    private const LOCK_PREFIX = 'check_in:attendee:';
    private const LOCK_SECONDS = 10;

    public function __construct(
        private CacheRepository $cache,
    )
    {
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     * @throws CannotCheckInException
     */
    public function withLock(int $attendeeId, callable $callback): mixed
    {
        $lock = $this->cache->lock(self::LOCK_PREFIX . $attendeeId, self::LOCK_SECONDS);

        try {
            return $lock->block(self::LOCK_SECONDS, $callback);
        } catch (LockTimeoutException) {
            throw new CannotCheckInException(
                __('This attendee is currently being checked in by another user. Please try again.')
            );
        }
    }
}
