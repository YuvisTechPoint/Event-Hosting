<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Campaign;

use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Repository\Interfaces\MessageSegmentRepositoryInterface;
use HiEvents\Services\Domain\Campaign\MessageSegmentResolverService;
use Illuminate\Support\Collection;

class PreviewMessageSegmentHandler
{
    public function __construct(
        private readonly MessageSegmentRepositoryInterface $messageSegmentRepository,
        private readonly MessageSegmentResolverService $segmentResolver,
    ) {
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function handle(int $eventId, int $segmentId): Collection
    {
        $segment = $this->messageSegmentRepository->findFirstWhere([
            'id' => $segmentId,
            'event_id' => $eventId,
        ]);

        if ($segment === null) {
            throw new ResourceNotFoundException(__('Message segment not found'));
        }

        return $this->segmentResolver->resolveAttendeeIdsForSegment($eventId, $segmentId);
    }
}
