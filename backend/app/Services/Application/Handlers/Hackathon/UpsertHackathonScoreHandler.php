<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Hackathon;

use HiEvents\DomainObjects\HackathonScoreDomainObject;
use HiEvents\Repository\Interfaces\HackathonScoreRepositoryInterface;
use HiEvents\Services\Application\Handlers\Hackathon\DTO\UpsertHackathonScoreDTO;

readonly class UpsertHackathonScoreHandler
{
    public function __construct(private HackathonScoreRepositoryInterface $scoreRepository)
    {
    }

    public function handle(int $judgeUserId, UpsertHackathonScoreDTO $dto): HackathonScoreDomainObject
    {
        return $this->scoreRepository->upsertScore(
            criteriaId: $dto->criteria_id,
            projectId: $dto->project_id,
            judgeUserId: $judgeUserId,
            score: $dto->score,
            feedback: $dto->feedback,
        );
    }
}
