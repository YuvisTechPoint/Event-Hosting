<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Domain\Waitlist;

use HiEvents\DomainObjects\ProductPriceDomainObject;
use HiEvents\DomainObjects\Status\OrderStatus;
use HiEvents\DomainObjects\Status\WaitlistEntryStatus;
use HiEvents\DomainObjects\WaitlistEntryDomainObject;
use HiEvents\Events\CapacityChangedEvent;
use HiEvents\Exceptions\ResourceConflictException;
use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Repository\Interfaces\OrderRepositoryInterface;
use HiEvents\Repository\Interfaces\ProductPriceRepositoryInterface;
use HiEvents\Repository\Interfaces\WaitlistEntryRepositoryInterface;
use HiEvents\Services\Domain\Waitlist\CancelWaitlistEntryService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class CancelWaitlistEntryServiceTest extends TestCase
{
    private CancelWaitlistEntryService $service;
    private WaitlistEntryRepositoryInterface $waitlistEntryRepository;
    private OrderRepositoryInterface $orderRepository;
    private ProductPriceRepositoryInterface $productPriceRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->waitlistEntryRepository = Mockery::mock(WaitlistEntryRepositoryInterface::class);
        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->productPriceRepository = Mockery::mock(ProductPriceRepositoryInterface::class);

        $databaseManager = Mockery::mock(DatabaseManager::class);
        $databaseManager->shouldReceive('transaction')->andReturnUsing(fn ($callback) => $callback());

        $this->service = new CancelWaitlistEntryService(
            $this->waitlistEntryRepository,
            $this->orderRepository,
            $databaseManager,
            $this->productPriceRepository,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCancelWaitingEntryMarksEntryCancelled(): void
    {
        Event::fake([CapacityChangedEvent::class]);

        $entry = new WaitlistEntryDomainObject();
        $entry->setId(5);
        $entry->setEventId(1);
        $entry->setStatus(WaitlistEntryStatus::WAITING->name);
        $entry->setOrderId(null);

        $cancelledEntry = new WaitlistEntryDomainObject();
        $cancelledEntry->setId(5);
        $cancelledEntry->setStatus(WaitlistEntryStatus::CANCELLED->name);

        $this->waitlistEntryRepository
            ->shouldReceive('findFirstWhere')
            ->once()
            ->with(['cancel_token' => 'token-abc'])
            ->andReturn($entry);

        $this->waitlistEntryRepository
            ->shouldReceive('updateWhere')
            ->once()
            ->with(
                Mockery::on(fn ($attrs) => $attrs['status'] === WaitlistEntryStatus::CANCELLED->name),
                ['id' => 5],
            );

        $this->waitlistEntryRepository
            ->shouldReceive('findById')
            ->once()
            ->with(5)
            ->andReturn($cancelledEntry);

        $result = $this->service->cancelByToken('token-abc');

        $this->assertSame(WaitlistEntryStatus::CANCELLED->name, $result->getStatus());
        Event::assertNotDispatched(CapacityChangedEvent::class);
    }

    public function testCancelOfferedEntryFreesCapacityForNextWaitlistEntry(): void
    {
        Event::fake([CapacityChangedEvent::class]);

        $entry = new WaitlistEntryDomainObject();
        $entry->setId(8);
        $entry->setEventId(1);
        $entry->setProductPriceId(20);
        $entry->setStatus(WaitlistEntryStatus::OFFERED->name);
        $entry->setOrderId(100);

        $productPrice = new ProductPriceDomainObject();
        $productPrice->setId(20);
        $productPrice->setProductId(3);

        $cancelledEntry = new WaitlistEntryDomainObject();
        $cancelledEntry->setId(8);
        $cancelledEntry->setStatus(WaitlistEntryStatus::CANCELLED->name);

        $this->waitlistEntryRepository
            ->shouldReceive('findFirstWhere')
            ->once()
            ->andReturn($entry);

        $this->orderRepository
            ->shouldReceive('deleteWhere')
            ->once()
            ->with(['id' => 100, 'status' => OrderStatus::RESERVED->name]);

        $this->waitlistEntryRepository
            ->shouldReceive('updateWhere')
            ->once();

        $this->productPriceRepository
            ->shouldReceive('findById')
            ->once()
            ->with(20)
            ->andReturn($productPrice);

        $this->waitlistEntryRepository
            ->shouldReceive('findById')
            ->once()
            ->with(8)
            ->andReturn($cancelledEntry);

        $this->service->cancelByToken('offer-token');

        Event::assertDispatched(CapacityChangedEvent::class, function (CapacityChangedEvent $event) {
            return $event->eventId === 1
                && $event->productId === 3
                && $event->productPriceId === 20;
        });
    }

    public function testCancelThrowsWhenEntryNotFound(): void
    {
        $this->waitlistEntryRepository
            ->shouldReceive('findFirstWhere')
            ->once()
            ->andReturnNull();

        $this->expectException(ResourceNotFoundException::class);

        $this->service->cancelByToken('missing-token');
    }

    public function testCancelThrowsWhenEntryAlreadyPurchased(): void
    {
        $entry = new WaitlistEntryDomainObject();
        $entry->setStatus(WaitlistEntryStatus::PURCHASED->name);

        $this->waitlistEntryRepository
            ->shouldReceive('findFirstWhere')
            ->once()
            ->andReturn($entry);

        $this->expectException(ResourceConflictException::class);

        $this->service->cancelByToken('purchased-token');
    }
}
