<?php

declare(strict_types=1);

namespace HiEvents\Events\Broadcasting;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class AttendeeRegisteredEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(
        public readonly int    $attendeeId,
        public readonly string $attendeeName,
        public readonly string $ticketType,
        public readonly string $registeredAt,
        public readonly int    $eventId,
    )
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('event.' . $this->eventId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'attendee.registered';
    }

    public function broadcastWith(): array
    {
        return [
            'attendee_id' => $this->attendeeId,
            'attendee_name' => $this->attendeeName,
            'ticket_type' => $this->ticketType,
            'registered_at' => $this->registeredAt,
            'event_id' => $this->eventId,
        ];
    }
}
