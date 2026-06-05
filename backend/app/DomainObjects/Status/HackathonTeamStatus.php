<?php

declare(strict_types=1);

namespace HiEvents\DomainObjects\Status;

enum HackathonTeamStatus: string
{
    case OPEN = 'OPEN';
    case LOCKED = 'LOCKED';
    case DISBANDED = 'DISBANDED';
}
