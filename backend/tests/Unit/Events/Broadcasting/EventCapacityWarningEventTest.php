<?php

namespace Tests\Unit\Events\Broadcasting;

use HiEvents\DomainObjects\Enums\CapacityWarningLevel;
use HiEvents\Events\Broadcasting\EventCapacityWarningEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Tests\TestCase;

class EventCapacityWarningEventTest extends TestCase
{
    public function testWarningLevelBroadcastName(): void
    {
        $event = new EventCapacityWarningEvent(
            eventId: 5,
            level: CapacityWarningLevel::WARNING,
            totalSold: 80,
            totalCapacity: 100,
            percentFull: 80.0,
        );

        $this->assertSame('capacity.warning', $event->broadcastAs());
        $this->assertBroadcastChannelsAndPayload($event, 5, 'warning', 80, 100, 80.0);
    }

    public function testSoldOutLevelBroadcastName(): void
    {
        $event = new EventCapacityWarningEvent(
            eventId: 5,
            level: CapacityWarningLevel::SOLD_OUT,
            totalSold: 100,
            totalCapacity: 100,
            percentFull: 100.0,
        );

        $this->assertSame('capacity.sold-out', $event->broadcastAs());
        $this->assertBroadcastChannelsAndPayload($event, 5, 'sold_out', 100, 100, 100.0);
    }

    private function assertBroadcastChannelsAndPayload(
        EventCapacityWarningEvent $event,
        int                       $eventId,
        string                    $level,
        int                       $totalSold,
        int                       $totalCapacity,
        float                     $percentFull,
    ): void
    {
        $channels = $event->broadcastOn();
        $this->assertCount(2, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-event.' . $eventId, $channels[0]->name);
        $this->assertInstanceOf(Channel::class, $channels[1]);
        $this->assertSame('event.' . $eventId . '.capacity', $channels[1]->name);

        $payload = $event->broadcastWith();
        $this->assertSame($eventId, $payload['event_id']);
        $this->assertSame($level, $payload['level']);
        $this->assertSame($totalSold, $payload['total_sold']);
        $this->assertSame($totalCapacity, $payload['total_capacity']);
        $this->assertSame($percentFull, $payload['percent_full']);
    }
}
