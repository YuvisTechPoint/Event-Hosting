<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Hackathon;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\DomainObjects\HackathonTeamDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\DTO\QueryParamsDTO;
use HiEvents\Repository\Interfaces\HackathonTeamRepositoryInterface;
use HiEvents\Resources\Hackathon\HackathonTeamResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetHackathonTeamsAction extends BaseAction
{
    public function __construct(private readonly HackathonTeamRepositoryInterface $repository)
    {
    }

    public function __invoke(Request $request, int $eventId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $teams = $this->repository->findByEventId($eventId, QueryParamsDTO::fromArray($request->query->all()));

        return $this->filterableResourceResponse(
            HackathonTeamResource::class,
            $teams,
            HackathonTeamDomainObject::class,
        );
    }
}
