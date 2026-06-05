<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Campaign;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Services\Application\Handlers\Campaign\DeleteMessageSegmentHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeleteMessageSegmentAction extends BaseAction
{
    public function __construct(
        private readonly DeleteMessageSegmentHandler $handler,
    ) {
    }

    public function __invoke(Request $request, int $eventId, int $segmentId): Response
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        try {
            $this->handler->handle($eventId, $segmentId);
        } catch (ResourceNotFoundException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        }

        return $this->deletedResponse();
    }
}
