<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Application\Handlers\Event;

use HiEvents\DomainObjects\AccountDomainObject;
use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\DomainObjects\Status\EventStatus;
use HiEvents\Exceptions\AccountNotVerifiedException;
use HiEvents\Repository\Interfaces\AccountRepositoryInterface;
use HiEvents\Repository\Interfaces\EventRepositoryInterface;
use HiEvents\Services\Application\Handlers\Event\DTO\UpdateEventStatusDTO;
use HiEvents\Services\Application\Handlers\Event\UpdateEventStatusHandler;
use HiEvents\Jobs\Event\Webhook\DispatchEventWebhookJob;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

class UpdateEventStatusHandlerTest extends TestCase
{
    private EventRepositoryInterface $eventRepository;
    private AccountRepositoryInterface $accountRepository;
    private UpdateEventStatusHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventRepository = Mockery::mock(EventRepositoryInterface::class);
        $this->accountRepository = Mockery::mock(AccountRepositoryInterface::class);
        $databaseManager = Mockery::mock(DatabaseManager::class);
        $databaseManager->shouldReceive('transaction')->andReturnUsing(fn ($callback) => $callback());

        $this->handler = new UpdateEventStatusHandler(
            $this->eventRepository,
            $this->accountRepository,
            Mockery::mock(LoggerInterface::class),
            $databaseManager,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testPublishEventSetsLiveStatusAndDispatchesWebhook(): void
    {
        Bus::fake();

        $account = new AccountDomainObject();
        $account->setId(1);
        $account->setAccountVerifiedAt(now()->toDateTimeString());

        $event = new EventDomainObject();
        $event->setId(42);
        $event->setStatus(EventStatus::LIVE->name);

        $this->accountRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($account);

        $this->eventRepository
            ->shouldReceive('updateWhere')
            ->once()
            ->with(
                ['status' => EventStatus::LIVE->name],
                ['id' => 42, 'account_id' => 1],
            );

        $this->eventRepository
            ->shouldReceive('findFirstWhere')
            ->once()
            ->andReturn($event);

        $result = $this->handler->handle(new UpdateEventStatusDTO(
            eventId: 42,
            accountId: 1,
            status: EventStatus::LIVE->name,
        ));

        $this->assertSame(EventStatus::LIVE->name, $result->getStatus());
        Bus::assertDispatched(DispatchEventWebhookJob::class);
    }

    public function testThrowsWhenAccountNotVerified(): void
    {
        $account = new AccountDomainObject();
        $account->setId(1);
        $account->setAccountVerifiedAt(null);

        $this->accountRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($account);

        $this->expectException(AccountNotVerifiedException::class);

        $this->handler->handle(new UpdateEventStatusDTO(
            eventId: 42,
            accountId: 1,
            status: EventStatus::LIVE->name,
        ));
    }
}
