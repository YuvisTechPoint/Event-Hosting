<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Hackathon;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\DomainObjects\HackathonProjectDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\DTO\QueryParamsDTO;
use HiEvents\Repository\Interfaces\HackathonProjectRepositoryInterface;
use HiEvents\Resources\Hackathon\HackathonProjectResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetHackathonProjectsAction extends BaseAction
{
    public function __construct(private readonly HackathonProjectRepositoryInterface $repository)
    {
    }

    public function __invoke(Request $request, int $eventId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $projects = $this->repository->findByEventId($eventId, QueryParamsDTO::fromArray($request->query->all()));

        return $this->filterableResourceResponse(
            HackathonProjectResource::class,
            $projects,
            HackathonProjectDomainObject::class,
        );
    }
}
