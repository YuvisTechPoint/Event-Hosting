<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Campaign;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Repository\Interfaces\MessageSegmentRepositoryInterface;
use HiEvents\Resources\Campaign\MessageSegmentResource;
use Illuminate\Http\JsonResponse;

class GetMessageSegmentAction extends BaseAction
{
    public function __construct(
        private readonly MessageSegmentRepositoryInterface $messageSegmentRepository,
    ) {
    }

    public function __invoke(int $eventId, int $segmentId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $segment = $this->messageSegmentRepository->findFirstWhere([
            'id' => $segmentId,
            'event_id' => $eventId,
        ]);

        if ($segment === null) {
            throw new ResourceNotFoundException(__('Message segment not found'));
        }

        return $this->resourceResponse(MessageSegmentResource::class, $segment);
    }
}
