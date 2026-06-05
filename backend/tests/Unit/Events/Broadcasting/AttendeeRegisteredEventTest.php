<?php

namespace Tests\Unit\Events\Broadcasting;

use HiEvents\Events\Broadcasting\AttendeeRegisteredEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;

class AttendeeRegisteredEventTest extends TestCase
{
    public function testBroadcastConfiguration(): void
    {
        $event = new AttendeeRegisteredEvent(
            attendeeId: 5,
            attendeeName: 'John D.',
            ticketType: 'VIP',
            registeredAt: '2026-06-06 14:30:00',
            eventId: 12,
        );

        $this->assertSame('attendee.registered', $event->broadcastAs());

        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-event.12', $channels[0]->name);

        $payload = $event->broadcastWith();
        $this->assertSame(5, $payload['attendee_id']);
        $this->assertSame('John D.', $payload['attendee_name']);
        $this->assertSame('VIP', $payload['ticket_type']);
        $this->assertSame('2026-06-06 14:30:00', $payload['registered_at']);
        $this->assertSame(12, $payload['event_id']);
    }
}
