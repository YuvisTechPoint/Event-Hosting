<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Campaign;

use HiEvents\DomainObjects\MessageSegmentDomainObject;
use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Repository\Interfaces\MessageSegmentRepositoryInterface;
use HiEvents\Services\Application\Handlers\Campaign\DTO\UpsertMessageSegmentDTO;

class UpdateMessageSegmentHandler
{
    public function __construct(
        private readonly MessageSegmentRepositoryInterface $messageSegmentRepository,
    ) {
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function handle(int $eventId, int $segmentId, UpsertMessageSegmentDTO $dto): MessageSegmentDomainObject
    {
        $existing = $this->messageSegmentRepository->findFirstWhere([
            'id' => $segmentId,
            'event_id' => $eventId,
        ]);

        if ($existing === null) {
            throw new ResourceNotFoundException(__('Message segment not found'));
        }

        return $this->messageSegmentRepository->updateFromArray($segmentId, [
            'name' => $dto->name,
            'rules' => $dto->rules,
        ]);
    }
}
