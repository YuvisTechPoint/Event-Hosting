<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Hackathon\DTO;

use HiEvents\DataTransferObjects\BaseDataObject;

class UpsertHackathonJudgingCriterionDTO extends BaseDataObject
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly int $max_score = 10,
        public readonly int $weight = 1,
        public readonly ?int $judging_round_id = null,
    ) {
    }
}
