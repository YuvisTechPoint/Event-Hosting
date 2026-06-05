<?php

declare(strict_types=1);

namespace HiEvents\Repository\Interfaces;

use HiEvents\DomainObjects\DripCampaignDomainObject;
use HiEvents\Http\DTO\QueryParamsDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * @extends RepositoryInterface<DripCampaignDomainObject>
 */
interface DripCampaignRepositoryInterface extends RepositoryInterface
{
    public function findByEventId(int $eventId, QueryParamsDTO $params): LengthAwarePaginator;

    public function findByIdAndEventId(int $id, int $eventId): ?DripCampaignDomainObject;

    public function findActiveByEventIdAndTrigger(int $eventId, string $trigger): Collection;
}
