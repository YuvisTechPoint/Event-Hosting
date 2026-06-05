<?php

declare(strict_types=1);

namespace HiEvents\Repository\Eloquent;

use HiEvents\DomainObjects\DripCampaignDomainObject;
use HiEvents\DomainObjects\DripCampaignStepDomainObject;
use HiEvents\DomainObjects\Generated\DripCampaignDomainObjectAbstract;
use HiEvents\DomainObjects\Status\DripCampaignStatus;
use HiEvents\Http\DTO\QueryParamsDTO;
use HiEvents\Models\DripCampaign;
use HiEvents\Repository\Eloquent\Value\Relationship;
use HiEvents\Repository\Interfaces\DripCampaignRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepository<DripCampaignDomainObject>
 */
class DripCampaignRepository extends BaseRepository implements DripCampaignRepositoryInterface
{
    protected function getModel(): string
    {
        return DripCampaign::class;
    }

    public function getDomainObject(): string
    {
        return DripCampaignDomainObject::class;
    }

    public function findByEventId(int $eventId, QueryParamsDTO $params): LengthAwarePaginator
    {
        $where = [
            [DripCampaignDomainObjectAbstract::EVENT_ID, '=', $eventId],
        ];

        if ($params->query) {
            $where[] = static function (Builder $builder) use ($params) {
                $builder->where(
                    DripCampaignDomainObjectAbstract::NAME,
                    'ilike',
                    '%' . $params->query . '%'
                );
            };
        }

        $this->model = $this->model->orderBy(DripCampaignDomainObjectAbstract::CREATED_AT, 'desc');
        $this->loadRelation(new Relationship(DripCampaignStepDomainObject::class, name: 'steps'));

        return $this->paginateWhere(
            where: $where,
            limit: $params->per_page,
            page: $params->page,
        );
    }

    public function findByIdAndEventId(int $id, int $eventId): ?DripCampaignDomainObject
    {
        $this->loadRelation(new Relationship(DripCampaignStepDomainObject::class, name: 'steps'));

        return $this->findFirstWhere([
            DripCampaignDomainObjectAbstract::ID => $id,
            DripCampaignDomainObjectAbstract::EVENT_ID => $eventId,
        ]);
    }

    public function findActiveByEventIdAndTrigger(int $eventId, string $trigger): Collection
    {
        $this->loadRelation(new Relationship(DripCampaignStepDomainObject::class, name: 'steps'));

        return $this->findWhere([
            DripCampaignDomainObjectAbstract::EVENT_ID => $eventId,
            DripCampaignDomainObjectAbstract::TRIGGER => $trigger,
            DripCampaignDomainObjectAbstract::STATUS => DripCampaignStatus::ACTIVE->value,
        ]);
    }
}
