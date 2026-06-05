<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Campaign;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\Request\Campaign\UpsertMessageSegmentRequest;
use HiEvents\Http\ResponseCodes;
use HiEvents\Resources\Campaign\MessageSegmentResource;
use HiEvents\Services\Application\Handlers\Campaign\CreateMessageSegmentHandler;
use HiEvents\Services\Application\Handlers\Campaign\DTO\UpsertMessageSegmentDTO;
use Illuminate\Http\JsonResponse;

class CreateMessageSegmentAction extends BaseAction
{
    public function __construct(
        private readonly CreateMessageSegmentHandler $handler,
    ) {
    }

    public function __invoke(UpsertMessageSegmentRequest $request, int $eventId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $segment = $this->handler->handle($eventId, new UpsertMessageSegmentDTO(
            name: $request->input('name'),
            rules: $request->input('rules', []),
        ));

        return $this->resourceResponse(
            resource: MessageSegmentResource::class,
            data: $segment,
            statusCode: ResponseCodes::HTTP_CREATED,
        );
    }
}
