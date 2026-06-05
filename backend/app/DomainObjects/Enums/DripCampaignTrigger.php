<?php

declare(strict_types=1);

namespace HiEvents\DomainObjects\Enums;

enum DripCampaignTrigger: string
{
    use BaseEnum;

    case ON_REGISTRATION = 'on_registration';
    case SCHEDULED = 'scheduled';
}
