<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Campaign;

use HiEvents\Http\DTO\QueryParamsDTO;
use HiEvents\Repository\Interfaces\DripCampaignRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListDripCampaignsHandler
{
    public function __construct(
        private readonly DripCampaignRepositoryInterface $dripCampaignRepository,
    ) {
    }

    public function handle(int $eventId, QueryParamsDTO $params): LengthAwarePaginator
    {
        return $this->dripCampaignRepository->findByEventId($eventId, $params);
    }
}
