<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Application\Handlers\Order\Payment\Stripe;

use HiEvents\Services\Application\Handlers\Order\Payment\Stripe\DTO\StripeWebhookDTO;
use HiEvents\Services\Application\Handlers\Order\Payment\Stripe\IncomingWebhookHandler;
use HiEvents\Services\Domain\Payment\Stripe\EventHandlers\AccountUpdateHandler;
use HiEvents\Services\Domain\Payment\Stripe\EventHandlers\ChargeRefundUpdatedHandler;
use HiEvents\Services\Domain\Payment\Stripe\EventHandlers\ChargeSucceededHandler;
use HiEvents\Services\Domain\Payment\Stripe\EventHandlers\PaymentIntentFailedHandler;
use HiEvents\Services\Domain\Payment\Stripe\EventHandlers\PaymentIntentSucceededHandler;
use HiEvents\Services\Domain\Payment\Stripe\EventHandlers\PayoutPaidHandler;
use HiEvents\Services\Infrastructure\Stripe\StripeConfigurationService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Log\Logger;
use Mockery;
use RuntimeException;
use Stripe\Event;
use Tests\TestCase;

class IncomingWebhookHandlerTest extends TestCase
{
    private const WEBHOOK_SECRET = 'whsec_test_secret_for_critical_path_tests';

    private IncomingWebhookHandler $handler;
    private PaymentIntentSucceededHandler $paymentIntentSucceededHandler;
    private CacheRepository $cache;
    private StripeConfigurationService $stripeConfigurationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentIntentSucceededHandler = Mockery::mock(PaymentIntentSucceededHandler::class);
        $this->cache = Mockery::mock(CacheRepository::class);
        $this->stripeConfigurationService = Mockery::mock(StripeConfigurationService::class);

        $this->handler = new IncomingWebhookHandler(
            Mockery::mock(ChargeRefundUpdatedHandler::class),
            Mockery::mock(ChargeSucceededHandler::class),
            $this->paymentIntentSucceededHandler,
            Mockery::mock(PaymentIntentFailedHandler::class),
            Mockery::mock(AccountUpdateHandler::class),
            Mockery::mock(PayoutPaidHandler::class),
            Mockery::mock(Logger::class)->shouldIgnoreMissing(),
            $this->cache,
            $this->stripeConfigurationService,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testSkipsDuplicateWebhookEventsForStripeRetry(): void
    {
        $payload = $this->buildPaymentIntentSucceededPayload('evt_retry_1', 'pi_retry_1');
        $signature = $this->signPayload($payload);

        $this->stripeConfigurationService
            ->shouldReceive('getAllWebhookSecrets')
            ->once()
            ->andReturn(['default' => self::WEBHOOK_SECRET]);

        $this->cache
            ->shouldReceive('has')
            ->once()
            ->with('stripe_event_evt_retry_1')
            ->andReturnTrue();

        $this->paymentIntentSucceededHandler->shouldNotReceive('handleEvent');

        $this->handler->handle(new StripeWebhookDTO(
            payload: $payload,
            headerSignature: $signature,
        ));
    }

    public function testProcessesFirstWebhookDeliveryAndMarksEventHandled(): void
    {
        $payload = $this->buildPaymentIntentSucceededPayload('evt_first_1', 'pi_first_1');
        $signature = $this->signPayload($payload);

        $this->stripeConfigurationService
            ->shouldReceive('getAllWebhookSecrets')
            ->once()
            ->andReturn(['default' => self::WEBHOOK_SECRET]);

        $this->cache
            ->shouldReceive('has')
            ->once()
            ->with('stripe_event_evt_first_1')
            ->andReturnFalse();

        $this->paymentIntentSucceededHandler
            ->shouldReceive('handleEvent')
            ->once();

        $this->cache
            ->shouldReceive('put')
            ->once()
            ->with('stripe_event_evt_first_1', true, Mockery::type('object'));

        $this->handler->handle(new StripeWebhookDTO(
            payload: $payload,
            headerSignature: $signature,
        ));
    }

    public function testRethrowsHandlerExceptionSoStripeCanRetryDelivery(): void
    {
        $payload = $this->buildPaymentIntentSucceededPayload('evt_fail_1', 'pi_fail_1');
        $signature = $this->signPayload($payload);

        $this->stripeConfigurationService
            ->shouldReceive('getAllWebhookSecrets')
            ->once()
            ->andReturn(['default' => self::WEBHOOK_SECRET]);

        $this->cache
            ->shouldReceive('has')
            ->once()
            ->andReturnFalse();

        $this->paymentIntentSucceededHandler
            ->shouldReceive('handleEvent')
            ->once()
            ->andThrow(new RuntimeException('Temporary database failure'));

        $this->cache->shouldNotReceive('put');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Temporary database failure');

        $this->handler->handle(new StripeWebhookDTO(
            payload: $payload,
            headerSignature: $signature,
        ));
    }

    private function buildPaymentIntentSucceededPayload(string $eventId, string $paymentIntentId): string
    {
        return json_encode([
            'id' => $eventId,
            'object' => 'event',
            'type' => Event::PAYMENT_INTENT_SUCCEEDED,
            'data' => [
                'object' => [
                    'id' => $paymentIntentId,
                    'object' => 'payment_intent',
                    'amount' => 5000,
                    'currency' => 'usd',
                ],
            ],
        ], JSON_THROW_ON_ERROR);
    }

    private function signPayload(string $payload): string
    {
        $timestamp = time();
        $signedPayload = $timestamp . '.' . $payload;
        $signature = hash_hmac('sha256', $signedPayload, self::WEBHOOK_SECRET);

        return 't=' . $timestamp . ',v1=' . $signature;
    }
}
