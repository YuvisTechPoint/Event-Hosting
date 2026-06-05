<?php

declare(strict_types=1);

namespace HiEvents\DomainObjects\Status;

enum HackathonProjectStatus: string
{
    case DRAFT = 'DRAFT';
    case SUBMITTED = 'SUBMITTED';
    case UNDER_REVIEW = 'UNDER_REVIEW';
    case FINALIST = 'FINALIST';
}
