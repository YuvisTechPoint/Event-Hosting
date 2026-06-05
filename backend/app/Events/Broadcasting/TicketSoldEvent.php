<?php

declare(strict_types=1);

namespace HiEvents\Events\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class TicketSoldEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(
        public readonly int    $eventId,
        public readonly int    $productId,
        public readonly string $productTitle,
        public readonly int    $quantitySold,
        public readonly int    $remainingCapacity,
        public readonly bool   $isUnlimited,
        public readonly int    $totalEventSold,
        public readonly ?int   $totalEventCapacity,
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
        return 'ticket.sold';
    }

    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'product_id' => $this->productId,
            'product_title' => $this->productTitle,
            'quantity_sold' => $this->quantitySold,
            'remaining_capacity' => $this->remainingCapacity,
            'is_unlimited' => $this->isUnlimited,
            'total_event_sold' => $this->totalEventSold,
            'total_event_capacity' => $this->totalEventCapacity,
        ];
    }
}
