<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Domain\Event;

use HiEvents\Services\Domain\Event\EventTicketAvailabilityCacheService;
use Illuminate\Contracts\Cache\Repository as Cache;
use Mockery;
use Tests\TestCase;

class EventTicketAvailabilityCacheServiceTest extends TestCase
{
    public function testInvalidateBumpsPublicEventVersionAndClearsQuantitiesCache(): void
    {
        $cache = Mockery::mock(Cache::class);
        $service = new EventTicketAvailabilityCacheService($cache);

        $cache->shouldReceive('get')->with('public_event_version.42', 1)->once()->andReturn(3);
        $cache->shouldReceive('put')
            ->once()
            ->with('public_event_version.42', 4, Mockery::type(\DateTimeInterface::class));
        $cache->shouldReceive('forget')->once()->with('event.42.available_product_quantities');

        $service->invalidate(42);
    }

    public function testBuildPublicEventCacheKeyIncludesVersionAndPromoHash(): void
    {
        $cache = Mockery::mock(Cache::class);
        $service = new EventTicketAvailabilityCacheService($cache);

        $cache->shouldReceive('get')->with('public_event_version.7', 1)->once()->andReturn(2);

        $key = $service->buildPublicEventCacheKey(7, 'SAVE10');

        $this->assertSame(
            'public_event.7.v2.' . md5('save10'),
            $key,
        );
    }
}
