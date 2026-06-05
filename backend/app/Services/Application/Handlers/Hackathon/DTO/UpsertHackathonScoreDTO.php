<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Hackathon\DTO;

use HiEvents\DataTransferObjects\BaseDataObject;

class UpsertHackathonScoreDTO extends BaseDataObject
{
    public function __construct(
        public readonly int $criteria_id,
        public readonly int $project_id,
        public readonly float $score,
        public readonly ?string $feedback = null,
    ) {
    }
}
