<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Campaign\DTO;

use HiEvents\DataTransferObjects\BaseDTO;

class UpsertMessageSegmentDTO extends BaseDTO
{
    public function __construct(
        public readonly string $name,
        public readonly array $rules = [],
    ) {
    }
}
