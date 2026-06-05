<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Application\Handlers\Attendee;

use HiEvents\DomainObjects\AttendeeDomainObject;
use HiEvents\DomainObjects\Enums\CheckInAction;
use HiEvents\DomainObjects\Status\AttendeeStatus;
use HiEvents\DomainObjects\UserDomainObject;
use HiEvents\Exceptions\CannotCheckInException;
use HiEvents\Repository\Interfaces\AttendeeRepositoryInterface;
use HiEvents\Repository\Interfaces\UserRepositoryInterface;
use HiEvents\Services\Application\Handlers\Attendee\CheckInAttendeeHandler;
use HiEvents\Services\Application\Handlers\Attendee\DTO\CheckInAttendeeDTO;
use HiEvents\Services\Domain\Event\EventAnalyticsFetchService;
use HiEvents\Services\Infrastructure\Broadcasting\AttendeeCheckInLockService;
use HiEvents\Services\Infrastructure\Broadcasting\EventRealtimeBroadcastService;
use Mockery;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Tests\TestCase;

class CheckInAttendeeHandlerTest extends TestCase
{
    private AttendeeRepositoryInterface $attendeeRepository;
    private UserRepositoryInterface $userRepository;
    private EventRealtimeBroadcastService $eventRealtimeBroadcastService;
    private CheckInAttendeeHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attendeeRepository = Mockery::mock(AttendeeRepositoryInterface::class);
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->eventRealtimeBroadcastService = Mockery::mock(EventRealtimeBroadcastService::class);

        $lockService = Mockery::mock(AttendeeCheckInLockService::class);
        $lockService->shouldReceive('withLock')->andReturnUsing(
            fn (int $attendeeId, callable $callback) => $callback()
        );

        $this->handler = new CheckInAttendeeHandler(
            $this->attendeeRepository,
            $this->userRepository,
            Mockery::mock(LoggerInterface::class),
            $lockService,
            $this->eventRealtimeBroadcastService,
            Mockery::mock(EventAnalyticsFetchService::class),
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCheckInBroadcastsRealtimeEvent(): void
    {
        $attendee = $this->createActiveAttendee(checkedInAt: null);
        $checkedInAttendee = $this->createActiveAttendee(checkedInAt: now()->toDateTimeString());

        $this->attendeeRepository
            ->shouldReceive('findFirstWhere')
            ->times(3)
            ->andReturn($attendee, $attendee, $checkedInAttendee);

        $this->attendeeRepository
            ->shouldReceive('updateWhere')
            ->once();

        $staffUser = new UserDomainObject();
        $staffUser->setFirstName('Staff');
        $staffUser->setLastName('Member');

        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(99)
            ->andReturn($staffUser);

        $this->eventRealtimeBroadcastService
            ->shouldReceive('broadcastAttendeeCheckedIn')
            ->once()
            ->with(
                Mockery::type(AttendeeDomainObject::class),
                'Staff Member',
                Mockery::type('string'),
            );

        $result = $this->handler->handle(new CheckInAttendeeDTO(
            event_id: 7,
            attendee_public_id: 'att-public-1',
            checked_in_by_user_id: 99,
            action: CheckInAction::CHECK_IN,
        ));

        $this->assertSame('att-public-1', $result->getPublicId());
    }

    public function testCheckInThrowsWhenAttendeeNotActive(): void
    {
        $attendee = new AttendeeDomainObject();
        $attendee->setPublicId('att-public-2');
        $attendee->setEventId(7);
        $attendee->setStatus(AttendeeStatus::CANCELLED->name);

        $this->attendeeRepository
            ->shouldReceive('findFirstWhere')
            ->twice()
            ->andReturn($attendee);

        $this->expectException(CannotCheckInException::class);

        $this->handler->handle(new CheckInAttendeeDTO(
            event_id: 7,
            attendee_public_id: 'att-public-2',
            checked_in_by_user_id: 99,
            action: CheckInAction::CHECK_IN,
        ));
    }

    public function testCheckInThrowsWhenAttendeeNotFound(): void
    {
        $this->attendeeRepository
            ->shouldReceive('findFirstWhere')
            ->once()
            ->andReturnNull();

        $this->expectException(ResourceNotFoundException::class);

        $this->handler->handle(new CheckInAttendeeDTO(
            event_id: 7,
            attendee_public_id: 'missing',
            checked_in_by_user_id: 99,
            action: CheckInAction::CHECK_IN,
        ));
    }

    private function createActiveAttendee(?string $checkedInAt): AttendeeDomainObject
    {
        $attendee = new AttendeeDomainObject();
        $attendee->setId(1);
        $attendee->setPublicId('att-public-1');
        $attendee->setEventId(7);
        $attendee->setFirstName('Jane');
        $attendee->setLastName('Doe');
        $attendee->setStatus(AttendeeStatus::ACTIVE->name);
        $attendee->setCheckedInAt($checkedInAt);

        return $attendee;
    }
}
