<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Campaign;

use HiEvents\DomainObjects\MessageSegmentDomainObject;
use HiEvents\Repository\Interfaces\MessageSegmentRepositoryInterface;
use HiEvents\Services\Application\Handlers\Campaign\DTO\UpsertMessageSegmentDTO;

class CreateMessageSegmentHandler
{
    public function __construct(
        private readonly MessageSegmentRepositoryInterface $messageSegmentRepository,
    ) {
    }

    public function handle(int $eventId, UpsertMessageSegmentDTO $dto): MessageSegmentDomainObject
    {
        return $this->messageSegmentRepository->create([
            'event_id' => $eventId,
            'name' => $dto->name,
            'rules' => $dto->rules,
        ]);
    }
}
