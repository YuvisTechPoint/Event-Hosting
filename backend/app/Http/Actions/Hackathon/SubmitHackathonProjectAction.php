<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Hackathon;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\ResponseCodes;
use HiEvents\Resources\Hackathon\HackathonProjectResource;
use HiEvents\Services\Application\Handlers\Hackathon\SubmitHackathonProjectHandler;
use Illuminate\Http\JsonResponse;

class SubmitHackathonProjectAction extends BaseAction
{
    public function __construct(private readonly SubmitHackathonProjectHandler $handler)
    {
    }

    public function __invoke(int $eventId, int $projectId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        try {
            $project = $this->handler->handle($eventId, $projectId);
        } catch (ResourceNotFoundException $e) {
            return $this->errorResponse($e->getMessage(), ResponseCodes::HTTP_NOT_FOUND);
        }

        return $this->resourceResponse(HackathonProjectResource::class, $project);
    }
}
