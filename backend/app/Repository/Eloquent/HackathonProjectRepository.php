<?php

declare(strict_types=1);

namespace HiEvents\Repository\Eloquent;

use HiEvents\DomainObjects\Generated\HackathonProjectDomainObjectAbstract;
use HiEvents\DomainObjects\HackathonProjectDomainObject;
use HiEvents\Http\DTO\QueryParamsDTO;
use HiEvents\Models\HackathonProject;
use HiEvents\Repository\Interfaces\HackathonProjectRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepository<HackathonProjectDomainObject>
 */
class HackathonProjectRepository extends BaseRepository implements HackathonProjectRepositoryInterface
{
    protected function getModel(): string
    {
        return HackathonProject::class;
    }

    public function getDomainObject(): string
    {
        return HackathonProjectDomainObject::class;
    }

    public function findByEventId(int $eventId, QueryParamsDTO $params): LengthAwarePaginator
    {
        $where = [[HackathonProjectDomainObjectAbstract::EVENT_ID, '=', $eventId]];

        if ($params->query) {
            $where[] = static function (Builder $builder) use ($params) {
                $builder
                    ->where(HackathonProjectDomainObjectAbstract::TITLE, 'ilike', '%' . $params->query . '%')
                    ->orWhere(HackathonProjectDomainObjectAbstract::SLUG, 'ilike', '%' . $params->query . '%');
            };
        }

        return $this->paginateWhere($where, $params->per_page, $params->page);
    }
}
