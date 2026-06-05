<?php

namespace HiEvents\DomainObjects\Enums;

enum CapacityWarningLevel: string
{
    case WARNING = 'warning';
    case SOLD_OUT = 'sold_out';

    public function broadcastAs(): string
    {
        return match ($this) {
            self::WARNING => 'capacity.warning',
            self::SOLD_OUT => 'capacity.sold-out',
        };
    }
}
