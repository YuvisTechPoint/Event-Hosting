<?php

declare(strict_types=1);

namespace HiEvents\Services\Domain\Event;

use Illuminate\Contracts\Cache\Repository as Cache;

class EventTicketAvailabilityCacheService
{
    private const PUBLIC_EVENT_VERSION_PREFIX = 'public_event_version.';

    public function __construct(
        private readonly Cache $cache,
    )
    {
    }

    public function getPublicEventCacheVersion(int $eventId): int
    {
        return (int) $this->cache->get(self::PUBLIC_EVENT_VERSION_PREFIX . $eventId, 1);
    }

    public function buildPublicEventCacheKey(int $eventId, ?string $promoCode): string
    {
        return 'public_event.'
            . $eventId
            . '.v'
            . $this->getPublicEventCacheVersion($eventId)
            . '.'
            . md5(strtolower($promoCode ?? ''));
    }

    public function invalidate(int $eventId): void
    {
        $versionKey = self::PUBLIC_EVENT_VERSION_PREFIX . $eventId;
        $this->cache->put(
            $versionKey,
            $this->getPublicEventCacheVersion($eventId) + 1,
            now()->addDays(30),
        );

        $this->cache->forget('event.' . $eventId . '.available_product_quantities');
    }
}
