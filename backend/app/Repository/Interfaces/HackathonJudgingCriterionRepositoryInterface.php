<?php

declare(strict_types=1);

namespace HiEvents\Repository\Interfaces;

use HiEvents\DomainObjects\HackathonJudgingCriterionDomainObject;
use HiEvents\Repository\Interfaces\RepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @extends RepositoryInterface<HackathonJudgingCriterionDomainObject>
 */
interface HackathonJudgingCriterionRepositoryInterface extends RepositoryInterface
{
    public function findByEventId(int $eventId): Collection;
}
