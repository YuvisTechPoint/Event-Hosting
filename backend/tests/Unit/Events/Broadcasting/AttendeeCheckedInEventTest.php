<?php

namespace Tests\Unit\Events\Broadcasting;

use HiEvents\Events\Broadcasting\AttendeeCheckedInEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;

class AttendeeCheckedInEventTest extends TestCase
{
    public function testBroadcastConfiguration(): void
    {
        $event = new AttendeeCheckedInEvent(
            attendeeId: 1,
            attendeePublicId: 'abc123',
            attendeeName: 'Jane Doe',
            ticketType: 'General Admission',
            checkedInBy: 'Staff User',
            checkedInAt: '2026-06-06 12:00:00',
            eventId: 42,
            totalCheckedIn: 10,
            totalCapacity: 100,
        );

        $this->assertSame('attendee.checked-in', $event->broadcastAs());
        $this->assertTrue($event->broadcastWhen());

        $channels = $event->broadcastOn();
        $this->assertCount(2, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-event.42', $channels[0]->name);
        $this->assertInstanceOf(Channel::class, $channels[1]);
        $this->assertSame('event.42.check-in', $channels[1]->name);

        $payload = $event->broadcastWith();
        $this->assertSame(1, $payload['attendee_id']);
        $this->assertSame('abc123', $payload['attendee_public_id']);
        $this->assertSame('Jane Doe', $payload['attendee_name']);
        $this->assertSame('General Admission', $payload['ticket_type']);
        $this->assertSame('Staff User', $payload['checked_in_by']);
        $this->assertSame('2026-06-06 12:00:00', $payload['checked_in_at']);
        $this->assertSame(42, $payload['event_id']);
        $this->assertSame(10, $payload['total_checked_in']);
        $this->assertSame(100, $payload['total_capacity']);
    }

    public function testBroadcastWhenReturnsFalseForCheckOut(): void
    {
        $event = new AttendeeCheckedInEvent(
            attendeeId: 1,
            attendeePublicId: 'abc123',
            attendeeName: 'Jane Doe',
            ticketType: 'General Admission',
            checkedInBy: null,
            checkedInAt: '2026-06-06 12:00:00',
            eventId: 42,
            totalCheckedIn: 9,
            totalCapacity: 100,
            isCheckedIn: false,
        );

        $this->assertFalse($event->broadcastWhen());
    }
}
