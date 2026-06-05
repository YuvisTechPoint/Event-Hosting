<?php

declare(strict_types=1);

namespace HiEvents\Repository\Interfaces;

use HiEvents\DomainObjects\MessageSegmentDomainObject;
use HiEvents\Http\DTO\QueryParamsDTO;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends RepositoryInterface<MessageSegmentDomainObject>
 */
interface MessageSegmentRepositoryInterface extends RepositoryInterface
{
    public function findByEventId(int $eventId, QueryParamsDTO $params): LengthAwarePaginator;
}
