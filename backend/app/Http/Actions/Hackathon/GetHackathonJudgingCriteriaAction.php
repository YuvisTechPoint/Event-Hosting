<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Hackathon;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Repository\Interfaces\HackathonJudgingCriterionRepositoryInterface;
use HiEvents\Resources\Hackathon\HackathonJudgingCriterionResource;
use Illuminate\Http\JsonResponse;

class GetHackathonJudgingCriteriaAction extends BaseAction
{
    public function __construct(private readonly HackathonJudgingCriterionRepositoryInterface $repository)
    {
    }

    public function __invoke(int $eventId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        return $this->resourceResponse(
            HackathonJudgingCriterionResource::class,
            $this->repository->findByEventId($eventId),
        );
    }
}
