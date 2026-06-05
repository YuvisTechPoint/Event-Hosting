<?php

declare(strict_types=1);

namespace HiEvents\Repository\Eloquent;

use HiEvents\DomainObjects\Generated\HackathonScoreDomainObjectAbstract;
use HiEvents\DomainObjects\HackathonScoreDomainObject;
use HiEvents\Models\HackathonScore;
use HiEvents\Repository\Interfaces\HackathonScoreRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepository<HackathonScoreDomainObject>
 */
class HackathonScoreRepository extends BaseRepository implements HackathonScoreRepositoryInterface
{
    protected function getModel(): string
    {
        return HackathonScore::class;
    }

    public function getDomainObject(): string
    {
        return HackathonScoreDomainObject::class;
    }

    public function findByProjectId(int $projectId): Collection
    {
        return $this->findWhere([
            HackathonScoreDomainObjectAbstract::PROJECT_ID => $projectId,
        ]);
    }

    public function upsertScore(int $criteriaId, int $projectId, int $judgeUserId, float $score, ?string $feedback): HackathonScoreDomainObject
    {
        $existing = $this->findFirstWhere([
            HackathonScoreDomainObjectAbstract::CRITERIA_ID => $criteriaId,
            HackathonScoreDomainObjectAbstract::PROJECT_ID => $projectId,
            HackathonScoreDomainObjectAbstract::JUDGE_USER_ID => $judgeUserId,
        ]);

        if ($existing) {
            return $this->updateFromArray($existing->getId(), [
                HackathonScoreDomainObjectAbstract::SCORE => $score,
                HackathonScoreDomainObjectAbstract::FEEDBACK => $feedback,
            ]);
        }

        return $this->create([
            HackathonScoreDomainObjectAbstract::CRITERIA_ID => $criteriaId,
            HackathonScoreDomainObjectAbstract::PROJECT_ID => $projectId,
            HackathonScoreDomainObjectAbstract::JUDGE_USER_ID => $judgeUserId,
            HackathonScoreDomainObjectAbstract::SCORE => $score,
            HackathonScoreDomainObjectAbstract::FEEDBACK => $feedback,
        ]);
    }
}
