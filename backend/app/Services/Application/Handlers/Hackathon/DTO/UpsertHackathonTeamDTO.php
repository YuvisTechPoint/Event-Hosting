<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Hackathon\DTO;

use HiEvents\DataTransferObjects\BaseDataObject;

class UpsertHackathonTeamDTO extends BaseDataObject
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        public readonly int $max_members = 4,
    ) {
    }
}
