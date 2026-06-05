<?php

declare(strict_types=1);

namespace HiEvents\Events\Broadcasting;

use HiEvents\DomainObjects\Enums\CapacityWarningLevel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class EventCapacityWarningEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(
        public readonly int                  $eventId,
        public readonly CapacityWarningLevel $level,
        public readonly int                  $totalSold,
        public readonly int                  $totalCapacity,
        public readonly float                $percentFull,
    )
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('event.' . $this->eventId),
            new Channel('event.' . $this->eventId . '.capacity'),
        ];
    }

    public function broadcastAs(): string
    {
        return $this->level->broadcastAs();
    }

    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'level' => $this->level->value,
            'total_sold' => $this->totalSold,
            'total_capacity' => $this->totalCapacity,
            'percent_full' => $this->percentFull,
        ];
    }
}
