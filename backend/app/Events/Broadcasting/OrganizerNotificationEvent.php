<?php

declare(strict_types=1);

namespace HiEvents\Events\Broadcasting;

use HiEvents\DomainObjects\Enums\OrganizerNotificationType;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class OrganizerNotificationEvent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(
        public readonly int                        $eventId,
        public readonly OrganizerNotificationType  $type,
        public readonly string                     $title,
        public readonly string                     $message,
        public readonly string                     $createdAt,
        public readonly array                      $metadata = [],
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
        return 'notification.new';
    }

    public function broadcastWith(): array
    {
        return [
            'event_id' => $this->eventId,
            'type' => $this->type->value,
            'title' => $this->title,
            'message' => $this->message,
            'created_at' => $this->createdAt,
            'metadata' => $this->metadata,
        ];
    }
}
