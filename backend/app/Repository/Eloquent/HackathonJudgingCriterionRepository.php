<?php

declare(strict_types=1);

namespace HiEvents\Repository\Eloquent;

use HiEvents\DomainObjects\Generated\HackathonJudgingCriterionDomainObjectAbstract;
use HiEvents\DomainObjects\HackathonJudgingCriterionDomainObject;
use HiEvents\Models\HackathonJudgingCriterion;
use HiEvents\Repository\Interfaces\HackathonJudgingCriterionRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepository<HackathonJudgingCriterionDomainObject>
 */
class HackathonJudgingCriterionRepository extends BaseRepository implements HackathonJudgingCriterionRepositoryInterface
{
    protected function getModel(): string
    {
        return HackathonJudgingCriterion::class;
    }

    public function getDomainObject(): string
    {
        return HackathonJudgingCriterionDomainObject::class;
    }

    public function findByEventId(int $eventId): Collection
    {
        return $this->findWhere([
            HackathonJudgingCriterionDomainObjectAbstract::EVENT_ID => $eventId,
        ])->sortBy(fn ($c) => $c->getSortOrder());
    }
}
