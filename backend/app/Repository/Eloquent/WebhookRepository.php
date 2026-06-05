<?php

namespace HiEvents\Repository\Eloquent;

use HiEvents\DomainObjects\Status\WebhookStatus;
use HiEvents\DomainObjects\WebhookDomainObject;
use HiEvents\Http\DTO\QueryParamsDTO;
use HiEvents\Models\Webhook;
use HiEvents\Repository\Interfaces\WebhookRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepository<WebhookDomainObject>
 */
class WebhookRepository extends BaseRepository implements WebhookRepositoryInterface
{
    protected function getModel(): string
    {
        return Webhook::class;
    }

    public function getDomainObject(): string
    {
        return WebhookDomainObject::class;
    }

    public function findEnabledByEventId(int $eventId): Collection
    {
        $results = $this->model::query()
            ->where('status', WebhookStatus::ENABLED->name)
            ->where(function ($query) use ($eventId) {
                $query->where('event_id', $eventId)
                    ->orWhere('organizer_id', function ($subquery) use ($eventId) {
                        $subquery->select('organizer_id')
                            ->from('events')
                            ->where('id', $eventId)
                            ->limit(1);
                    });
            })
            ->get();

        $this->resetModel();

        return $this->handleResults($results);
    }

    public function paginateByFilters(array $where, QueryParamsDTO $params): LengthAwarePaginator
    {
        $this->model = $this->model->orderBy('id', 'desc');

        return $this->paginateWhere(
            where: $where,
            limit: $params->per_page,
            page: $params->page,
        );
    }
}
