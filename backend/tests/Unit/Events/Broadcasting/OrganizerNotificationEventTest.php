<?php

namespace Tests\Unit\Events\Broadcasting;

use HiEvents\DomainObjects\Enums\OrganizerNotificationType;
use HiEvents\Events\Broadcasting\OrganizerNotificationEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;

class OrganizerNotificationEventTest extends TestCase
{
    public function testBroadcastConfiguration(): void
    {
        $event = new OrganizerNotificationEvent(
            eventId: 8,
            type: OrganizerNotificationType::PURCHASE,
            title: 'New ticket purchase',
            message: '2 ticket(s) purchased in order #ORD-1',
            createdAt: '2026-06-06 15:00:00',
            metadata: ['order_id' => 99, 'buyer_email' => 'buyer@example.com'],
        );

        $this->assertSame('notification.new', $event->broadcastAs());

        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-event.8', $channels[0]->name);

        $payload = $event->broadcastWith();
        $this->assertSame(8, $payload['event_id']);
        $this->assertSame('purchase', $payload['type']);
        $this->assertSame('New ticket purchase', $payload['title']);
        $this->assertSame('2 ticket(s) purchased in order #ORD-1', $payload['message']);
        $this->assertSame('2026-06-06 15:00:00', $payload['created_at']);
        $this->assertSame(['order_id' => 99, 'buyer_email' => 'buyer@example.com'], $payload['metadata']);
    }
}
