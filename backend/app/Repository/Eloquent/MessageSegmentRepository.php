<?php

declare(strict_types=1);

namespace HiEvents\Repository\Eloquent;

use HiEvents\DomainObjects\Generated\MessageSegmentDomainObjectAbstract;
use HiEvents\DomainObjects\MessageSegmentDomainObject;
use HiEvents\Http\DTO\QueryParamsDTO;
use HiEvents\Models\MessageSegment;
use HiEvents\Repository\Interfaces\MessageSegmentRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepository<MessageSegmentDomainObject>
 */
class MessageSegmentRepository extends BaseRepository implements MessageSegmentRepositoryInterface
{
    protected function getModel(): string
    {
        return MessageSegment::class;
    }

    public function getDomainObject(): string
    {
        return MessageSegmentDomainObject::class;
    }

    public function findByEventId(int $eventId, QueryParamsDTO $params): LengthAwarePaginator
    {
        $where = [
            [MessageSegmentDomainObjectAbstract::EVENT_ID, '=', $eventId],
        ];

        if ($params->query) {
            $where[] = static function (Builder $builder) use ($params) {
                $builder->where(
                    MessageSegmentDomainObjectAbstract::NAME,
                    'ilike',
                    '%' . $params->query . '%'
                );
            };
        }

        $this->model = $this->model->orderBy(MessageSegmentDomainObjectAbstract::NAME);

        return $this->paginateWhere(
            where: $where,
            limit: $params->per_page,
            page: $params->page,
        );
    }
}
