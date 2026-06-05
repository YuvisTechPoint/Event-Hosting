<?php

namespace Tests\Unit\Services\Application\Handlers\Event;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\DomainObjects\PromoCodeDomainObject;
use HiEvents\Repository\Interfaces\EventRepositoryInterface;
use HiEvents\Repository\Interfaces\PromoCodeRepositoryInterface;
use HiEvents\Services\Application\Handlers\Event\DTO\GetPublicEventDTO;
use HiEvents\Services\Application\Handlers\Event\GetPublicEventHandler;
use HiEvents\Services\Domain\Event\EventPageViewIncrementService;
use HiEvents\Services\Domain\Event\EventTicketAvailabilityCacheService;
use HiEvents\Services\Domain\Product\ProductFilterService;
use Illuminate\Config\Repository as Config;
use Illuminate\Contracts\Cache\Repository as Cache;
use Mockery as m;
use Tests\TestCase;

class GetPublicEventHandlerTest extends TestCase
{
    private EventRepositoryInterface $eventRepository;
    private PromoCodeRepositoryInterface $promoCodeRepository;
    private ProductFilterService $ticketFilterService;
    private EventPageViewIncrementService $eventPageViewIncrementService;
    private Cache $cache;
    private Config $config;
    private GetPublicEventHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventRepository = m::mock(EventRepositoryInterface::class);
        $this->promoCodeRepository = m::mock(PromoCodeRepositoryInterface::class);
        $this->ticketFilterService = m::mock(ProductFilterService::class);
        $this->eventPageViewIncrementService = m::mock(EventPageViewIncrementService::class);
        $this->cache = m::mock(Cache::class);
        $this->config = m::mock(Config::class);

        $this->config->shouldReceive('get')->with('app.public_event_cache_ttl')->andReturn(null)->byDefault();

        $ticketAvailabilityCacheService = m::mock(EventTicketAvailabilityCacheService::class);
        $ticketAvailabilityCacheService
            ->shouldReceive('buildPublicEventCacheKey')
            ->andReturnUsing(fn (int $eventId, ?string $promoCode) => 'public_event.' . $eventId . '.v1.' . md5(strtolower($promoCode ?? '')));

        $this->handler = new GetPublicEventHandler(
            $this->eventRepository,
            $this->promoCodeRepository,
            $this->ticketFilterService,
            $this->eventPageViewIncrementService,
            $ticketAvailabilityCacheService,
            $this->cache,
            $this->config,
        );
    }

    public function testHandleWithoutPromoCodeAndUnauthenticatedUser(): void
    {
        $data = new GetPublicEventDTO(eventId: 1, isAuthenticated: false, ipAddress: '127.0.0.1', promoCode: null);
        $event = new EventDomainObject();
        $event->setProductCategories(collect());

        $this->setupEventRepositoryMock($event, $data->eventId);
        $this->promoCodeRepository->shouldReceive('findFirstWhere')->once()->andReturnNull();
        $this->ticketFilterService->shouldReceive('filter')->once()->withAnyArgs()->andReturn(collect());
        $this->eventPageViewIncrementService->shouldReceive('increment')->once()->with($data->eventId, $data->ipAddress);

        $this->handler->handle($data);
    }

    public function testHandleWithInvalidPromoCode(): void
    {
        $data = new GetPublicEventDTO(eventId: 1, isAuthenticated: false, ipAddress: '127.0.0.1', promoCode: 'INVALID');
        $event = new EventDomainObject();
        $event->setProductCategories(collect());
        $promoCode = m::mock(PromoCodeDomainObject::class)->makePartial();
        $promoCode->shouldReceive('isValid')->andReturn(false);

        $this->setupEventRepositoryMock($event, $data->eventId);
        $this->promoCodeRepository->shouldReceive('findFirstWhere')->once()->andReturn($promoCode);
        $this->ticketFilterService->shouldReceive('filter')->once()->withAnyArgs()->andReturn(collect());
        $this->eventPageViewIncrementService->shouldReceive('increment')->once()->with($data->eventId, $data->ipAddress);

        $this->handler->handle($data);
    }

    public function testHandleWithValidPromoCode(): void
    {
        $data = new GetPublicEventDTO(eventId: 1, isAuthenticated: false, ipAddress: '127.0.0.1', promoCode: 'VALID');
        $event = new EventDomainObject();
        $event->setProductCategories(collect());
        $promoCode = m::mock(PromoCodeDomainObject::class)->makePartial();
        $promoCode->shouldReceive('isValid')->andReturn(true);

        $this->setupEventRepositoryMock($event, $data->eventId);
        $this->promoCodeRepository->shouldReceive('findFirstWhere')->once()->andReturn($promoCode);
        $this->ticketFilterService->shouldReceive('filter')->once()->withAnyArgs()->andReturn(collect());
        $this->eventPageViewIncrementService->shouldReceive('increment')->once()->with($data->eventId, $data->ipAddress);

        $this->handler->handle($data);
    }

    public function testHandleUsesCacheForUnauthenticatedRequests(): void
    {
        $data = new GetPublicEventDTO(eventId: 1, isAuthenticated: false, ipAddress: '127.0.0.1', promoCode: null);
        $event = new EventDomainObject();
        $event->setProductCategories(collect());

        $this->config->shouldReceive('get')->with('app.public_event_cache_ttl')->andReturn(300);
        $this->cache->shouldReceive('get')->once()->andReturn($event);
        $this->eventRepository->shouldNotReceive('findById');
        $this->eventPageViewIncrementService->shouldReceive('increment')->once()->with($data->eventId, $data->ipAddress);

        $result = $this->handler->handle($data);

        $this->assertSame($event, $result);
    }

    private function setupEventRepositoryMock($event, $eventId): void
    {
        $this->eventRepository->shouldReceive('loadRelation')->andReturnSelf()->times(4);
        $this->eventRepository->shouldReceive('findById')->with($eventId)->andReturn($event);
    }
}
