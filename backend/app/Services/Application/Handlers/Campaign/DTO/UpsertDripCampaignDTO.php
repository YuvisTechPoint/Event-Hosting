<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Campaign\DTO;

use HiEvents\DataTransferObjects\BaseDTO;
use HiEvents\DomainObjects\Enums\DripCampaignTrigger;
use HiEvents\DomainObjects\Status\DripCampaignStatus;

class UpsertDripCampaignDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly DripCampaignTrigger $trigger,
        public readonly DripCampaignStatus $status,
        public readonly bool $use_default_template = true,
        public readonly ?string $scheduled_at = null,
        /** @var UpsertDripCampaignStepDTO[]|null */
        public readonly ?array $steps = null,
    ) {
    }
}
