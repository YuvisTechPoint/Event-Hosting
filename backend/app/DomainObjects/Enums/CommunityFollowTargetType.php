<?php

namespace HiEvents\DomainObjects\Enums;

enum CommunityFollowTargetType: string
{
    use BaseEnum;

    case ORGANIZER = 'ORGANIZER';
    case DEVELOPER_PROFILE = 'DEVELOPER_PROFILE';
}
