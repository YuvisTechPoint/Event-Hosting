<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Application\Handlers\Order\Payment\Stripe;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\DomainObjects\EventSettingDomainObject;
use HiEvents\DomainObjects\Enums\StripePlatform;
use HiEvents\DomainObjects\OrderDomainObject;
use HiEvents\DomainObjects\OrganizerDomainObject;
use HiEvents\DomainObjects\Status\OrderRefundStatus;
use HiEvents\DomainObjects\StripePaymentDomainObject;
use HiEvents\Exceptions\RefundNotPossibleException;
use HiEvents\Repository\Interfaces\EventRepositoryInterface;
use HiEvents\Repository\Interfaces\OrderRepositoryInterface;
use HiEvents\Services\Application\Handlers\Order\DTO\RefundOrderDTO;
use HiEvents\Services\Application\Handlers\Order\Payment\Stripe\RefundOrderHandler;
use HiEvents\Services\Domain\Order\OrderCancelService;
use HiEvents\Services\Domain\Payment\Stripe\StripePaymentIntentRefundService;
use HiEvents\Services\Infrastructure\Broadcasting\EventRealtimeBroadcastService;
use HiEvents\Services\Infrastructure\Stripe\StripeClientFactory;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\DatabaseManager;
use Mockery;
use Stripe\StripeClient;
use Tests\TestCase;

class RefundOrderHandlerTest extends TestCase
{
    private RefundOrderHandler $handler;
    private StripePaymentIntentRefundService $refundService;
    private OrderRepositoryInterface $orderRepository;
    private EventRepositoryInterface $eventRepository;
    private EventRealtimeBroadcastService $eventRealtimeBroadcastService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->refundService = Mockery::mock(StripePaymentIntentRefundService::class);
        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->eventRepository = Mockery::mock(EventRepositoryInterface::class);
        $this->eventRealtimeBroadcastService = Mockery::mock(EventRealtimeBroadcastService::class);

        $databaseManager = Mockery::mock(DatabaseManager::class);
        $databaseManager->shouldReceive('transaction')->andReturnUsing(fn ($callback) => $callback());

        $this->handler = new RefundOrderHandler(
            $this->refundService,
            $this->orderRepository,
            $this->eventRepository,
            Mockery::mock(Mailer::class),
            Mockery::mock(OrderCancelService::class),
            $databaseManager,
            Mockery::mock(StripeClientFactory::class),
            $this->eventRealtimeBroadcastService,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testRefundMarksOrderPendingAndBroadcastsNotification(): void
    {
        $order = $this->createRefundableOrder();
        $event = $this->createEvent();
        $pendingOrder = clone $order;
        $pendingOrder->setRefundStatus(OrderRefundStatus::REFUND_PENDING->name);

        $this->orderRepository
            ->shouldReceive('loadRelation')
            ->once()
            ->andReturnSelf();
        $this->orderRepository
            ->shouldReceive('findFirstWhere')
            ->once()
            ->with(['event_id' => 7, 'id' => 10])
            ->andReturn($order);

        $this->eventRepository
            ->shouldReceive('loadRelation')
            ->twice()
            ->andReturnSelf();
        $this->eventRepository
            ->shouldReceive('findById')
            ->once()
            ->with(7)
            ->andReturn($event);

        $stripeClientFactory = Mockery::mock(StripeClientFactory::class);
        $stripeClientFactory
            ->shouldReceive('createForPlatform')
            ->once()
            ->with(StripePlatform::IRELAND)
            ->andReturn(Mockery::mock(StripeClient::class));

        $this->refundService
            ->shouldReceive('refundPayment')
            ->once();

        $this->eventRealtimeBroadcastService
            ->shouldReceive('broadcastRefundRequestedNotification')
            ->once()
            ->with($order, 25.0, 'USD');

        $this->orderRepository
            ->shouldReceive('updateFromArray')
            ->once()
            ->with(10, [
                'refund_status' => OrderRefundStatus::REFUND_PENDING->name,
            ])
            ->andReturn($pendingOrder);

        $handler = new RefundOrderHandler(
            $this->refundService,
            $this->orderRepository,
            $this->eventRepository,
            Mockery::mock(Mailer::class),
            Mockery::mock(OrderCancelService::class),
            Mockery::mock(DatabaseManager::class)->shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb())->getMock(),
            $stripeClientFactory,
            $this->eventRealtimeBroadcastService,
        );

        $result = $handler->handle(new RefundOrderDTO(
            event_id: 7,
            order_id: 10,
            amount: 25.0,
            cancel_order: false,
            notify_buyer: false,
        ));

        $this->assertSame(OrderRefundStatus::REFUND_PENDING->name, $result->getRefundStatus());
    }

    public function testRefundThrowsWhenOrderHasNoStripePayment(): void
    {
        $order = new OrderDomainObject();
        $order->setId(10);
        $order->setStripePayment(null);

        $this->orderRepository
            ->shouldReceive('loadRelation')
            ->once()
            ->andReturnSelf();
        $this->orderRepository
            ->shouldReceive('findFirstWhere')
            ->once()
            ->andReturn($order);

        $this->expectException(RefundNotPossibleException::class);

        $this->handler->handle(new RefundOrderDTO(
            event_id: 7,
            order_id: 10,
            amount: 25.0,
            cancel_order: false,
            notify_buyer: false,
        ));
    }

    private function createRefundableOrder(): OrderDomainObject
    {
        $stripePayment = new StripePaymentDomainObject();
        $stripePayment->setStripePlatform(StripePlatform::IRELAND->value);

        $order = new OrderDomainObject();
        $order->setId(10);
        $order->setEventId(7);
        $order->setCurrency('USD');
        $order->setRefundStatus(null);
        $order->setStripePayment($stripePayment);

        return $order;
    }

    private function createEvent(): EventDomainObject
    {
        $event = new EventDomainObject();
        $event->setId(7);
        $event->setOrganizer(new OrganizerDomainObject());
        $event->setEventSettings(new EventSettingDomainObject());

        return $event;
    }
}
