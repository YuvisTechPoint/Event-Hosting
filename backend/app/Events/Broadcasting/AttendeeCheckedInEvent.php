<?php

declare(strict_types=1);

namespace HiEvents\Events\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class AttendeeCheckedInEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(
        public readonly int    $attendeeId,
        public readonly string $attendeePublicId,
        public readonly string $attendeeName,
        public readonly string $ticketType,
        public readonly ?string $checkedInBy,
        public readonly string $checkedInAt,
        public readonly int    $eventId,
        public readonly int    $totalCheckedIn,
        public readonly ?int   $totalCapacity,
        public readonly bool   $isCheckedIn = true,
    )
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('event.' . $this->eventId),
            new Channel('event.' . $this->eventId . '.check-in'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'attendee.checked-in';
    }

    public function broadcastWith(): array
    {
        return [
            'attendee_id' => $this->attendeeId,
            'attendee_public_id' => $this->attendeePublicId,
            'attendee_name' => $this->attendeeName,
            'ticket_type' => $this->ticketType,
            'checked_in_by' => $this->checkedInBy,
            'checked_in_at' => $this->checkedInAt,
            'event_id' => $this->eventId,
            'total_checked_in' => $this->totalCheckedIn,
            'total_capacity' => $this->totalCapacity,
        ];
    }

    public function broadcastWhen(): bool
    {
        return $this->isCheckedIn;
    }
}
