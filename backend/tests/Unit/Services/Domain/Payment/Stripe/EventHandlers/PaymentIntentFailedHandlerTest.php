<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Domain\Payment\Stripe\EventHandlers;

use HiEvents\DomainObjects\Generated\OrderDomainObjectAbstract;
use HiEvents\DomainObjects\OrderDomainObject;
use HiEvents\DomainObjects\Status\OrderPaymentStatus;
use HiEvents\DomainObjects\StripePaymentDomainObject;
use HiEvents\Repository\Eloquent\StripePaymentsRepository;
use HiEvents\Repository\Interfaces\OrderRepositoryInterface;
use HiEvents\Services\Domain\Payment\Stripe\EventHandlers\PaymentIntentFailedHandler;
use HiEvents\Services\Domain\Payment\Stripe\StripePaymentUpdateFromPaymentIntentService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event as EventFacade;
use Mockery;
use Stripe\PaymentIntent;
use Tests\TestCase;

class PaymentIntentFailedHandlerTest extends TestCase
{
    private PaymentIntentFailedHandler $handler;
    private OrderRepositoryInterface $orderRepository;
    private StripePaymentsRepository $stripePaymentsRepository;
    private StripePaymentUpdateFromPaymentIntentService $stripePaymentUpdateService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->stripePaymentsRepository = Mockery::mock(StripePaymentsRepository::class);
        $this->stripePaymentUpdateService = Mockery::mock(StripePaymentUpdateFromPaymentIntentService::class);

        $databaseManager = Mockery::mock(DatabaseManager::class);
        $databaseManager->shouldReceive('transaction')->andReturnUsing(fn ($callback) => $callback());

        $this->handler = new PaymentIntentFailedHandler(
            $this->orderRepository,
            $this->stripePaymentsRepository,
            $databaseManager,
            $this->stripePaymentUpdateService,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testFailedPaymentUpdatesOrderToPaymentFailed(): void
    {
        EventFacade::fake();

        $paymentIntent = $this->createFailedPaymentIntent(
            id: 'pi_declined_0002',
            declineCode: 'card_declined',
            message: 'Your card was declined.',
        );

        $order = $this->createOrder();
        $stripePayment = $this->createStripePayment($order);

        $this->stripePaymentsRepository
            ->shouldReceive('loadRelation')
            ->once()
            ->andReturnSelf();
        $this->stripePaymentsRepository
            ->shouldReceive('findFirstWhere')
            ->once()
            ->andReturn($stripePayment);

        $this->stripePaymentUpdateService
            ->shouldReceive('updateStripePaymentInfo')
            ->once()
            ->with($paymentIntent, $stripePayment);

        $updatedOrder = clone $order;
        $updatedOrder->setPaymentStatus(OrderPaymentStatus::PAYMENT_FAILED->name);

        $this->orderRepository
            ->shouldReceive('loadRelation')
            ->once()
            ->andReturnSelf();
        $this->orderRepository
            ->shouldReceive('updateFromArray')
            ->once()
            ->with(10, [
                OrderDomainObjectAbstract::PAYMENT_STATUS => OrderPaymentStatus::PAYMENT_FAILED->name,
            ])
            ->andReturn($updatedOrder);

        $this->handler->handleEvent($paymentIntent);
    }

    public function testDeclinedCardStoresLastPaymentError(): void
    {
        EventFacade::fake();

        $paymentIntent = $this->createFailedPaymentIntent(
            id: 'pi_declined_4242424244244242',
            declineCode: 'card_declined',
            message: 'Your card was declined.',
        );

        $order = $this->createOrder();
        $stripePayment = $this->createStripePayment($order);

        $this->setupSuccessfulFailureHandling($paymentIntent, $stripePayment, $order);

        $this->stripePaymentUpdateService
            ->shouldReceive('updateStripePaymentInfo')
            ->once()
            ->with(
                Mockery::on(fn (PaymentIntent $intent) => $intent->last_payment_error?->decline_code === 'card_declined'),
                $stripePayment,
            );

        $this->handler->handleEvent($paymentIntent);
    }

    public function testInsufficientFundsStoresLastPaymentError(): void
    {
        EventFacade::fake();

        $paymentIntent = $this->createFailedPaymentIntent(
            id: 'pi_insufficient_9995',
            declineCode: 'insufficient_funds',
            message: 'Your card has insufficient funds.',
        );

        $order = $this->createOrder();
        $stripePayment = $this->createStripePayment($order);

        $this->setupSuccessfulFailureHandling($paymentIntent, $stripePayment, $order);

        $this->stripePaymentUpdateService
            ->shouldReceive('updateStripePaymentInfo')
            ->once()
            ->with(
                Mockery::on(fn (PaymentIntent $intent) => $intent->last_payment_error?->decline_code === 'insufficient_funds'),
                $stripePayment,
            );

        $this->handler->handleEvent($paymentIntent);
    }

    public function testThreeDSecureAuthenticationRequiredIsHandledAsFailedAttempt(): void
    {
        EventFacade::fake();

        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_3ds_3155',
            'status' => 'requires_action',
            'amount' => 5000,
            'currency' => 'usd',
            'last_payment_error' => [
                'code' => 'authentication_required',
                'decline_code' => 'authentication_required',
                'message' => 'Your card was declined. This transaction requires authentication.',
            ],
        ]);

        $order = $this->createOrder();
        $stripePayment = $this->createStripePayment($order);

        $this->stripePaymentsRepository
            ->shouldReceive('loadRelation')
            ->once()
            ->andReturnSelf();
        $this->stripePaymentsRepository
            ->shouldReceive('findFirstWhere')
            ->once()
            ->andReturn($stripePayment);

        $this->stripePaymentUpdateService
            ->shouldReceive('updateStripePaymentInfo')
            ->once()
            ->with(
                Mockery::on(fn (PaymentIntent $intent) => $intent->last_payment_error?->decline_code === 'authentication_required'),
                $stripePayment,
            );

        $this->orderRepository
            ->shouldReceive('loadRelation')
            ->once()
            ->andReturnSelf();
        $this->orderRepository
            ->shouldReceive('updateFromArray')
            ->once()
            ->with(10, [
                OrderDomainObjectAbstract::PAYMENT_STATUS => OrderPaymentStatus::PAYMENT_FAILED->name,
            ]);

        $this->handler->handleEvent($paymentIntent);
    }

    private function setupSuccessfulFailureHandling(
        PaymentIntent $paymentIntent,
        StripePaymentDomainObject $stripePayment,
        OrderDomainObject $order,
    ): void {
        $this->stripePaymentsRepository
            ->shouldReceive('loadRelation')
            ->once()
            ->andReturnSelf();
        $this->stripePaymentsRepository
            ->shouldReceive('findFirstWhere')
            ->once()
            ->andReturn($stripePayment);

        $this->stripePaymentUpdateService
            ->shouldReceive('updateStripePaymentInfo')
            ->once();

        $updatedOrder = clone $order;
        $updatedOrder->setPaymentStatus(OrderPaymentStatus::PAYMENT_FAILED->name);

        $this->orderRepository
            ->shouldReceive('loadRelation')
            ->once()
            ->andReturnSelf();
        $this->orderRepository
            ->shouldReceive('updateFromArray')
            ->once()
            ->andReturn($updatedOrder);
    }

    private function createFailedPaymentIntent(string $id, string $declineCode, string $message): PaymentIntent
    {
        return PaymentIntent::constructFrom([
            'id' => $id,
            'status' => 'requires_payment_method',
            'amount' => 5000,
            'currency' => 'usd',
            'last_payment_error' => [
                'code' => 'card_declined',
                'decline_code' => $declineCode,
                'message' => $message,
            ],
        ]);
    }

    private function createOrder(): OrderDomainObject
    {
        $order = new OrderDomainObject();
        $order->setId(10);
        $order->setEventId(7);
        $order->setPaymentStatus(OrderPaymentStatus::AWAITING_PAYMENT->name);
        $order->setOrderItems(new Collection());

        return $order;
    }

    private function createStripePayment(OrderDomainObject $order): StripePaymentDomainObject
    {
        $stripePayment = new StripePaymentDomainObject();
        $stripePayment->setOrderId($order->getId());
        $stripePayment->setOrder($order);

        return $stripePayment;
    }
}
