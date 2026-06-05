<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Campaign;

use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Repository\Interfaces\MessageSegmentRepositoryInterface;

class DeleteMessageSegmentHandler
{
    public function __construct(
        private readonly MessageSegmentRepositoryInterface $messageSegmentRepository,
    ) {
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function handle(int $eventId, int $segmentId): void
    {
        $existing = $this->messageSegmentRepository->findFirstWhere([
            'id' => $segmentId,
            'event_id' => $eventId,
        ]);

        if ($existing === null) {
            throw new ResourceNotFoundException(__('Message segment not found'));
        }

        $this->messageSegmentRepository->deleteById($segmentId);
    }
}
