<?php

namespace Tests\Unit\Events\Broadcasting;

use HiEvents\Events\Broadcasting\TicketSoldEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;

class TicketSoldEventTest extends TestCase
{
    public function testBroadcastsToPrivateAndPublicCapacityChannels(): void
    {
        $event = new TicketSoldEvent(
            eventId: 7,
            productId: 3,
            productTitle: 'VIP',
            quantitySold: 2,
            remainingCapacity: 18,
            isUnlimited: false,
            totalEventSold: 82,
            totalEventCapacity: 100,
        );

        $this->assertSame('ticket.sold', $event->broadcastAs());

        $channels = $event->broadcastOn();
        $this->assertCount(2, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-event.7', $channels[0]->name);
        $this->assertInstanceOf(Channel::class, $channels[1]);
        $this->assertSame('event.7.capacity', $channels[1]->name);

        $payload = $event->broadcastWith();
        $this->assertSame(7, $payload['event_id']);
        $this->assertSame(3, $payload['product_id']);
        $this->assertSame('VIP', $payload['product_title']);
        $this->assertSame(2, $payload['quantity_sold']);
        $this->assertSame(18, $payload['remaining_capacity']);
        $this->assertFalse($payload['is_unlimited']);
        $this->assertSame(82, $payload['total_event_sold']);
        $this->assertSame(100, $payload['total_event_capacity']);
    }
}
