<?php

namespace HiEvents\Services\Application\Handlers\Webhook;

use HiEvents\Http\DTO\QueryParamsDTO;
use HiEvents\Repository\Interfaces\WebhookRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetWebhooksHandler
{
    public function __construct(
        private readonly WebhookRepositoryInterface $webhookRepository,
    )
    {
    }

    public function handler(
        int $accountId,
        QueryParamsDTO $params,
        ?int $eventId = null,
        ?int $organizerId = null,
    ): LengthAwarePaginator
    {
        $where = ['account_id' => $accountId];
        if ($eventId !== null) {
            $where['event_id'] = $eventId;
        }
        if ($organizerId !== null) {
            $where['organizer_id'] = $organizerId;
        }

        return $this->webhookRepository->paginateByFilters($where, $params);
    }
}
