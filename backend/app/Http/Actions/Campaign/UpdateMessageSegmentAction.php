<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Campaign;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\Request\Campaign\UpsertMessageSegmentRequest;
use HiEvents\Resources\Campaign\MessageSegmentResource;
use HiEvents\Services\Application\Handlers\Campaign\DTO\UpsertMessageSegmentDTO;
use HiEvents\Services\Application\Handlers\Campaign\UpdateMessageSegmentHandler;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UpdateMessageSegmentAction extends BaseAction
{
    public function __construct(
        private readonly UpdateMessageSegmentHandler $handler,
    ) {
    }

    public function __invoke(UpsertMessageSegmentRequest $request, int $eventId, int $segmentId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        try {
            $segment = $this->handler->handle($eventId, $segmentId, new UpsertMessageSegmentDTO(
                name: $request->input('name'),
                rules: $request->input('rules', []),
            ));
        } catch (ResourceNotFoundException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        }

        return $this->resourceResponse(MessageSegmentResource::class, $segment);
    }
}
