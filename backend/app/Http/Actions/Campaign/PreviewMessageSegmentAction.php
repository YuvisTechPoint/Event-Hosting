<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Campaign;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Services\Application\Handlers\Campaign\PreviewMessageSegmentHandler;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PreviewMessageSegmentAction extends BaseAction
{
    public function __construct(
        private readonly PreviewMessageSegmentHandler $handler,
    ) {
    }

    public function __invoke(int $eventId, int $segmentId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        try {
            $attendeeIds = $this->handler->handle($eventId, $segmentId);
        } catch (ResourceNotFoundException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        }

        return $this->jsonResponse([
            'attendee_count' => $attendeeIds->count(),
            'attendee_ids' => $attendeeIds->values()->all(),
        ]);
    }
}
