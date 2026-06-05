<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Campaign\DTO;

use HiEvents\DataTransferObjects\BaseDTO;

class UpsertDripCampaignStepDTO extends BaseDTO
{
    public function __construct(
        public readonly int $step_order,
        public readonly int $delay_hours,
        public readonly ?int $email_template_id = null,
        public readonly ?string $subject = null,
        public readonly ?string $body = null,
        public readonly ?int $message_segment_id = null,
    ) {
    }
}
