<?php

namespace HiEvents\DomainObjects\Enums;

enum OrganizerNotificationType: string
{
    case PURCHASE = 'purchase';
    case REFUND = 'refund';
    case CHECK_IN = 'check_in';
    case MESSAGE = 'message';
}
