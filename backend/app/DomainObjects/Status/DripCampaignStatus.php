<?php

declare(strict_types=1);

namespace HiEvents\DomainObjects\Status;

use HiEvents\DomainObjects\Enums\BaseEnum;

enum DripCampaignStatus: string
{
    use BaseEnum;

    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case PAUSED = 'paused';
    case ARCHIVED = 'archived';
}
