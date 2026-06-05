<?php

declare(strict_types=1);

namespace HiEvents\DomainObjects\Enums;

enum HackathonTeamMemberRole: string
{
    case LEADER = 'LEADER';
    case MEMBER = 'MEMBER';
}
