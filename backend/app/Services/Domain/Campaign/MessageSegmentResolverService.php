<?php

declare(strict_types=1);

namespace HiEvents\Services\Domain\Campaign;

use HiEvents\Repository\Interfaces\AttendeeRepositoryInterface;
use HiEvents\Repository\Interfaces\MessageSegmentRepositoryInterface;
use Illuminate\Support\Collection;

class MessageSegmentResolverService
{
    public function __construct(
        private readonly AttendeeRepositoryInterface $attendeeRepository,
        private readonly MessageSegmentRepositoryInterface $messageSegmentRepository,
    ) {
    }

    public function resolveAttendeeIds(int $eventId, array $rules): Collection
    {
        return $this->attendeeRepository->findIdsMatchingSegmentRules($eventId, $rules);
    }

    public function resolveAttendeeIdsForSegment(int $eventId, ?int $segmentId): Collection
    {
        if ($segmentId === null) {
            return $this->resolveAttendeeIds($eventId, []);
        }

        $segment = $this->messageSegmentRepository->findFirstWhere([
            'id' => $segmentId,
            'event_id' => $eventId,
        ]);

        if ($segment === null) {
            return collect();
        }

        return $this->resolveAttendeeIds($eventId, $segment->getRules());
    }

    public function attendeeMatchesSegment(int $eventId, int $attendeeId, ?int $segmentId): bool
    {
        if ($segmentId === null) {
            return true;
        }

        return $this->resolveAttendeeIdsForSegment($eventId, $segmentId)->contains($attendeeId);
    }
}
