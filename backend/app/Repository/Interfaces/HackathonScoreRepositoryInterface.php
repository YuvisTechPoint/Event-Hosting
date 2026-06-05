<?php

declare(strict_types=1);

namespace HiEvents\Repository\Interfaces;

use HiEvents\DomainObjects\HackathonScoreDomainObject;
use HiEvents\Repository\Interfaces\RepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @extends RepositoryInterface<HackathonScoreDomainObject>
 */
interface HackathonScoreRepositoryInterface extends RepositoryInterface
{
    public function findByProjectId(int $projectId): Collection;

    public function upsertScore(int $criteriaId, int $projectId, int $judgeUserId, float $score, ?string $feedback): HackathonScoreDomainObject;
}
