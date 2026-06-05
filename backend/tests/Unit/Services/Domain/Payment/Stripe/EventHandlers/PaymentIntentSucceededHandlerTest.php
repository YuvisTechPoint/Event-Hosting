<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Domain\Payment\Stripe\EventHandlers;

use Carbon\Carbon;
use HiEvents\DomainObjects\EventSettingDomainObject;
use HiEvents\DomainObjects\OrderDomainObject;
use HiEvents\DomainObjects\Status\OrderPaymentStatus;
use HiEvents\DomainObjects\Status\OrderStatus;
use HiEvents\DomainObjects\StripePaymentDomainObject;
use HiEvents\Exceptions\CannotAcceptPaymentException;
use HiEvents\Repository\Eloquent\StripePaymentsRepository;
use HiEvents\Repository\Interfaces\AffiliateRepositoryInterface;
use HiEvents\Repository\Interfaces\AttendeeRepositoryInterface;
use HiEvents\Repository\Interfaces\EventSettingsRepositoryInterface;
use HiEvents\Repository\Interfaces\OrderRepositoryInterface;
use HiEvents\Services\Domain\Order\OrderApplicationFeeService;
use HiEvents\Services\Domain\Payment\Stripe\EventHandlers\PaymentIntentSucceededHandler;
use HiEvents\Services\Domain\Payment\Stripe\StripeRefundExpiredOrderService;
use HiEvents\Services\Domain\Product\ProductQuantityUpdateService;
use HiEvents\Services\Infrastructure\Broadcasting\EventRealtimeBroadcastService;
use HiEvents\Services\Infrastructure\DomainEvents\DomainEventDispatcherService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Mockery;
use Psr\Log\LoggerInterface;
use Stripe\PaymentIntent;
use Tests\TestCase;

class PaymentIntentSucceededHandlerTest extends TestCase
{
    private PaymentIntentSucceededHandler $handler;
    private OrderRepositoryInterface $orderRepository;
    private StripePaymentsRepository $stripePaymentsRepository;
    private CacheRepository $cache;
    private EventRealtimeBroadcastService $eventRealtimeBroadcastService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->stripePaymentsRepository = Mockery::mock(StripePaymentsRepository::class);
        $this->cache = Mockery::mock(CacheRepository::class);
        $this->eventRealtimeBroadcastService = Mockery::mock(EventRealtimeBroadcastService::class);

        $databaseManager = Mockery::mock(DatabaseManager::class);
        $databaseManager->shouldReceive('transaction')->andReturnUsing(fn ($callback) => $callback());

        $this->handler = new PaymentIntentSucceededHandler(
            $this->orderRepository,
            $this->stripePaymentsRepository,
            Mockery::mock(AffiliateRepositoryInterface::class),
            Mockery::mock(ProductQuantityUpdateService::class),
            Mockery::mock(StripeRefundExpiredOrderService::class),
            Mockery::mock(AttendeeRepositoryInterface::class),
            $databaseManager,
            Mockery::mock(LoggerInterface::class),
            $this->cache,
            Mockery::mock(DomainEventDispatcherService::class),
            Mockery::mock(OrderApplicationFeeService::class),
            Mockery::mock(EventSettingsRepositoryInterface::class),
            $this->eventRealtimeBroadcastService,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testSkipsAlreadyHandledPaymentIntent(): void
    {
        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_success_4242',
            'amount' => 5000,
            'currency' => 'usd',
        ]);

        $this->cache
            ->shouldReceive('has')
            ->once()
            ->with('payment_intent_handled_pi_success_4242')
            ->andReturnTrue();

        $this->stripePaymentsRepository->shouldNotReceive('findFirstWhere');

        $this->handler->handleEvent($paymentIntent);

        $this->assertTrue(true);
    }

    public function testSuccessfulPaymentCompletesOrder(): void
    {
        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_success_4242',
            'amount' => 5000,
            'amount_received' => 5000,
            'currency' => 'usd',
            'payment_method' => 'pm_card_visa',
            'latest_charge' => 'ch_success',
            'application_fee_amount' => 0,
        ]);

        $order = $this->createAwaitingPaymentOrder(totalGross: 50.00, currency: 'USD');
        $stripePayment = $this->createStripePayment($order, 'pi_success_4242');
        $completedOrder = clone $order;
        $completedOrder->setPaymentStatus(OrderPaymentStatus::PAYMENT_RECEIVED->name);
        $completedOrder->setStatus(OrderStatus::COMPLETED->name);

        $this->cache->shouldReceive('has')->andReturnFalse();
        $this->cache->shouldReceive('put')->once();

        $this->stripePaymentsRepository
            ->shouldReceive('loadRelation')
            ->once()
            ->andReturnSelf();
        $this->stripePaymentsRepository
            ->shouldReceive('findFirstWhere')
            ->once()
            ->andReturn($stripePayment);
        $this->stripePaymentsRepository
            ->shouldReceive('updateWhere')
            ->once();

        $this->orderRepository
            ->shouldReceive('loadRelation')
            ->once()
            ->andReturnSelf();
        $this->orderRepository
            ->shouldReceive('updateFromArray')
            ->once()
            ->andReturn($completedOrder);

        $eventSettings = new EventSettingDomainObject();
        $eventSettings->setEnableInvoicing(false);

        $eventSettingsRepository = Mockery::mock(EventSettingsRepositoryInterface::class);
        $eventSettingsRepository
            ->shouldReceive('findFirstWhere')
            ->once()
            ->andReturn($eventSettings);

        $attendeeRepository = Mockery::mock(AttendeeRepositoryInterface::class);
        $attendeeRepository->shouldReceive('updateWhere')->once();

        $quantityUpdateService = Mockery::mock(ProductQuantityUpdateService::class);
        $quantityUpdateService->shouldReceive('updateQuantitiesFromOrder')->once();

        $domainEventDispatcher = Mockery::mock(DomainEventDispatcherService::class);
        $domainEventDispatcher->shouldReceive('dispatch')->once();

        $orderApplicationFeeService = Mockery::mock(OrderApplicationFeeService::class);
        $orderApplicationFeeService->shouldReceive('createOrderApplicationFee')->once();

        $this->eventRealtimeBroadcastService
            ->shouldReceive('handleOrderCompleted')
            ->once()
            ->with($completedOrder);

        $handler = new PaymentIntentSucceededHandler(
            $this->orderRepository,
            $this->stripePaymentsRepository,
            Mockery::mock(AffiliateRepositoryInterface::class),
            $quantityUpdateService,
            Mockery::mock(StripeRefundExpiredOrderService::class),
            $attendeeRepository,
            Mockery::mock(DatabaseManager::class)->shouldReceive('transaction')->andReturnUsing(fn ($cb) => $cb())->getMock(),
            Mockery::mock(LoggerInterface::class),
            $this->cache,
            $domainEventDispatcher,
            $orderApplicationFeeService,
            $eventSettingsRepository,
            $this->eventRealtimeBroadcastService,
        );

        $handler->handleEvent($paymentIntent);
    }

    public function testThrowsWhenOrderAlreadyPaid(): void
    {
        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_declined_retry',
            'amount' => 5000,
            'currency' => 'usd',
        ]);

        $order = $this->createAwaitingPaymentOrder(totalGross: 50.00, currency: 'USD');
        $order->setPaymentStatus(OrderPaymentStatus::PAYMENT_RECEIVED->name);
        $stripePayment = $this->createStripePayment($order, 'pi_declined_retry');

        $this->cache->shouldReceive('has')->andReturnFalse();

        $this->stripePaymentsRepository
            ->shouldReceive('loadRelation')
            ->once()
            ->andReturnSelf();
        $this->stripePaymentsRepository
            ->shouldReceive('findFirstWhere')
            ->once()
            ->andReturn($stripePayment);

        $this->expectException(CannotAcceptPaymentException::class);

        $this->handler->handleEvent($paymentIntent);
    }

    public function testThrowsWhenPaymentAmountDoesNotMatchOrderTotal(): void
    {
        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_amount_mismatch',
            'amount' => 1000,
            'currency' => 'usd',
        ]);

        $order = $this->createAwaitingPaymentOrder(totalGross: 50.00, currency: 'USD');
        $stripePayment = $this->createStripePayment($order, 'pi_amount_mismatch');

        $this->cache->shouldReceive('has')->andReturnFalse();

        $this->stripePaymentsRepository
            ->shouldReceive('loadRelation')
            ->once()
            ->andReturnSelf();
        $this->stripePaymentsRepository
            ->shouldReceive('findFirstWhere')
            ->once()
            ->andReturn($stripePayment);

        $this->expectException(CannotAcceptPaymentException::class);

        $this->handler->handleEvent($paymentIntent);
    }

    private function createAwaitingPaymentOrder(float $totalGross, string $currency): OrderDomainObject
    {
        $order = new OrderDomainObject();
        $order->setId(10);
        $order->setEventId(7);
        $order->setTotalGross($totalGross);
        $order->setCurrency($currency);
        $order->setPaymentStatus(OrderPaymentStatus::AWAITING_PAYMENT->name);
        $order->setStatus(OrderStatus::RESERVED->name);
        $order->setReservedUntil(Carbon::now()->addHour()->toDateTimeString());
        $order->setOrderItems(new Collection());

        return $order;
    }

    private function createStripePayment(OrderDomainObject $order, string $paymentIntentId): StripePaymentDomainObject
    {
        $stripePayment = new StripePaymentDomainObject();
        $stripePayment->setOrderId($order->getId());
        $stripePayment->setOrder($order);

        return $stripePayment;
    }
}
