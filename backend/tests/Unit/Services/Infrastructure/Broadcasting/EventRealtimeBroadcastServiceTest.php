<?php

namespace Tests\Unit\Services\Infrastructure\Broadcasting;

use HiEvents\DomainObjects\Enums\CapacityWarningLevel;
use HiEvents\DomainObjects\Enums\ProductType;
use HiEvents\DomainObjects\OrderDomainObject;
use HiEvents\DomainObjects\OrderItemDomainObject;
use HiEvents\Events\Broadcasting\EventCapacityWarningEvent;
use HiEvents\Events\Broadcasting\TicketSoldEvent;
use HiEvents\Services\Domain\Product\AvailableProductQuantitiesFetchService;
use HiEvents\Services\Domain\Product\DTO\AvailableProductQuantitiesDTO;
use HiEvents\Services\Domain\Product\DTO\AvailableProductQuantitiesResponseDTO;
use Illuminate\Support\Collection;
use HiEvents\Repository\Interfaces\AttendeeRepositoryInterface;
use HiEvents\Repository\Interfaces\EventRepositoryInterface;
use HiEvents\Repository\Interfaces\ProductRepositoryInterface;
use HiEvents\Services\Domain\Event\EventStatsFetchService;
use HiEvents\Services\Infrastructure\Broadcasting\EventRealtimeBroadcastService;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class EventRealtimeBroadcastServiceTest extends TestCase
{
    private function createEventStatsFetchService(): EventStatsFetchService
    {
        return new EventStatsFetchService(
            Mockery::mock(DatabaseManager::class),
            Mockery::mock(EventRepositoryInterface::class),
            Mockery::mock(CacheRepository::class),
            Mockery::mock(ConfigRepository::class),
        );
    }

    public function testDispatchesCapacityWarningAtEightyPercent(): void
    {
        Event::fake([EventCapacityWarningEvent::class]);

        $db = Mockery::mock(DatabaseManager::class);
        $db->shouldReceive('selectOne')->once()->andReturn((object) [
            'total_sold' => 80,
            'total_capacity' => 100,
            'has_unlimited' => false,
        ]);

        $cache = Mockery::mock(CacheRepository::class);
        $cache->shouldReceive('has')
            ->with('capacity_warning:event:9:warning')
            ->andReturn(false);
        $cache->shouldReceive('put')
            ->with('capacity_warning:event:9:warning', true, Mockery::type('object'))
            ->once();

        $service = new EventRealtimeBroadcastService(
            Mockery::mock(AttendeeRepositoryInterface::class),
            Mockery::mock(ProductRepositoryInterface::class),
            $this->createEventStatsFetchService(),
            Mockery::mock(AvailableProductQuantitiesFetchService::class),
            $db,
            $cache,
        );

        $service->checkAndBroadcastCapacityWarnings(9);

        Event::assertDispatched(EventCapacityWarningEvent::class, function (EventCapacityWarningEvent $event) {
            return $event->eventId === 9
                && $event->level === CapacityWarningLevel::WARNING
                && $event->totalSold === 80;
        });
    }

    public function testDispatchesSoldOutAtOneHundredPercent(): void
    {
        Event::fake([EventCapacityWarningEvent::class]);

        $db = Mockery::mock(DatabaseManager::class);
        $db->shouldReceive('selectOne')->once()->andReturn((object) [
            'total_sold' => 100,
            'total_capacity' => 100,
            'has_unlimited' => false,
        ]);

        $cache = Mockery::mock(CacheRepository::class);
        $cache->shouldReceive('has')
            ->with('capacity_warning:event:9:sold_out')
            ->andReturn(false);
        $cache->shouldReceive('put')
            ->with('capacity_warning:event:9:sold_out', true, Mockery::type('object'))
            ->once();

        $service = new EventRealtimeBroadcastService(
            Mockery::mock(AttendeeRepositoryInterface::class),
            Mockery::mock(ProductRepositoryInterface::class),
            $this->createEventStatsFetchService(),
            Mockery::mock(AvailableProductQuantitiesFetchService::class),
            $db,
            $cache,
        );

        $service->checkAndBroadcastCapacityWarnings(9);

        Event::assertDispatched(EventCapacityWarningEvent::class, function (EventCapacityWarningEvent $event) {
            return $event->level === CapacityWarningLevel::SOLD_OUT;
        });
    }

    public function testHandleOrderCompletedDispatchesTicketSoldEventForPublicCapacityUpdates(): void
    {
        Event::fake([TicketSoldEvent::class]);

        $db = Mockery::mock(DatabaseManager::class);
        $db->shouldReceive('selectOne')->atLeast()->once()->andReturn((object) [
            'total_sold' => 18,
            'total_capacity' => 20,
            'has_unlimited' => false,
        ]);

        $cache = Mockery::mock(CacheRepository::class);
        $cache->shouldReceive('has')
            ->with('capacity_warning:event:7:warning')
            ->andReturn(false);
        $cache->shouldReceive('put')
            ->with('capacity_warning:event:7:warning', true, Mockery::type('object'))
            ->once();

        $orderItem = new OrderItemDomainObject();
        $orderItem->setProductType(ProductType::TICKET->name);
        $orderItem->setProductId(3);
        $orderItem->setProductPriceId(30);
        $orderItem->setQuantity(2);
        $orderItem->setItemName('General Admission');

        $order = new OrderDomainObject();
        $order->setId(100);
        $order->setEventId(7);
        $order->setPublicId('ORD-100');
        $order->setOrderItems(new Collection([$orderItem]));

        $availableQuantitiesService = Mockery::mock(AvailableProductQuantitiesFetchService::class);
        $availableQuantitiesService
            ->shouldReceive('getAvailableProductQuantities')
            ->once()
            ->with(7, true)
            ->andReturn(new AvailableProductQuantitiesResponseDTO(
                productQuantities: collect([
                    new AvailableProductQuantitiesDTO(
                        product_id: 3,
                        price_id: 30,
                        product_title: 'General Admission',
                        price_label: null,
                        quantity_available: 2,
                        quantity_reserved: 0,
                        initial_quantity_available: 20,
                    ),
                ]),
            ));

        $attendeeRepository = Mockery::mock(AttendeeRepositoryInterface::class);
        $attendeeRepository->shouldReceive('findWhere')->once()->andReturn(collect());

        $service = new EventRealtimeBroadcastService(
            $attendeeRepository,
            Mockery::mock(ProductRepositoryInterface::class),
            $this->createEventStatsFetchService(),
            $availableQuantitiesService,
            $db,
            $cache,
        );

        $service->handleOrderCompleted($order);

        Event::assertDispatched(TicketSoldEvent::class, function (TicketSoldEvent $event) {
            return $event->eventId === 7
                && $event->productId === 3
                && $event->quantitySold === 2
                && $event->remainingCapacity === 2
                && $event->totalEventSold === 18
                && $event->totalEventCapacity === 20;
        });
    }
}
