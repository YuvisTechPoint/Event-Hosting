<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Campaign;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Resources\Campaign\MessageSegmentResource;
use HiEvents\Services\Application\Handlers\Campaign\ListMessageSegmentsHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetMessageSegmentsAction extends BaseAction
{
    public function __construct(
        private readonly ListMessageSegmentsHandler $handler,
    ) {
    }

    public function __invoke(Request $request, int $eventId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $segments = $this->handler->handle($eventId, $this->getPaginationQueryParams($request));

        return $this->resourceResponse(
            resource: MessageSegmentResource::class,
            data: $segments,
        );
    }
}
