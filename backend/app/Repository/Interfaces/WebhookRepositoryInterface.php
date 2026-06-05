<?php

namespace HiEvents\Repository\Interfaces;

use HiEvents\DomainObjects\WebhookDomainObject;
use HiEvents\Http\DTO\QueryParamsDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * @extends RepositoryInterface<WebhookDomainObject>
 */
interface WebhookRepositoryInterface extends RepositoryInterface
{
    public function findEnabledByEventId(int $eventId): Collection;

    public function paginateByFilters(array $where, QueryParamsDTO $params): LengthAwarePaginator;
}
