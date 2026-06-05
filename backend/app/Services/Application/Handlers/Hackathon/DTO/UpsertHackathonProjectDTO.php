<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Hackathon\DTO;

use HiEvents\DataTransferObjects\BaseDataObject;

class UpsertHackathonProjectDTO extends BaseDataObject
{
    public function __construct(
        public readonly int $team_id,
        public readonly string $title,
        public readonly ?string $description = null,
        public readonly ?string $repository_url = null,
        public readonly ?string $demo_url = null,
        public readonly ?array $tech_stack = null,
    ) {
    }
}
