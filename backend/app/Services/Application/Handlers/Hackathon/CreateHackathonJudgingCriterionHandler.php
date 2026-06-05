<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Hackathon;

use HiEvents\DomainObjects\HackathonJudgingCriterionDomainObject;
use HiEvents\Repository\Interfaces\HackathonJudgingCriterionRepositoryInterface;
use HiEvents\Services\Application\Handlers\Hackathon\DTO\UpsertHackathonJudgingCriterionDTO;

readonly class CreateHackathonJudgingCriterionHandler
{
    public function __construct(private HackathonJudgingCriterionRepositoryInterface $criterionRepository)
    {
    }

    public function handle(int $eventId, UpsertHackathonJudgingCriterionDTO $dto): HackathonJudgingCriterionDomainObject
    {
        return $this->criterionRepository->create([
            'event_id' => $eventId,
            'judging_round_id' => $dto->judging_round_id,
            'name' => $dto->name,
            'description' => $dto->description,
            'max_score' => $dto->max_score,
            'weight' => $dto->weight,
        ]);
    }
}
