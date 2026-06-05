<?php

declare(strict_types=1);

namespace HiEvents\Repository\Interfaces;

use HiEvents\DomainObjects\HackathonProjectDomainObject;
use HiEvents\Http\DTO\QueryParamsDTO;
use HiEvents\Repository\Interfaces\RepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends RepositoryInterface<HackathonProjectDomainObject>
 */
interface HackathonProjectRepositoryInterface extends RepositoryInterface
{
    public function findByEventId(int $eventId, QueryParamsDTO $params): LengthAwarePaginator;
}
