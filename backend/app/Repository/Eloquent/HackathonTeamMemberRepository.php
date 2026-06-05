<?php

declare(strict_types=1);

namespace HiEvents\Repository\Eloquent;

use HiEvents\DomainObjects\Generated\HackathonTeamMemberDomainObjectAbstract;
use HiEvents\DomainObjects\HackathonTeamMemberDomainObject;
use HiEvents\Models\HackathonTeamMember;
use HiEvents\Repository\Interfaces\HackathonTeamMemberRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @extends BaseRepository<HackathonTeamMemberDomainObject>
 */
class HackathonTeamMemberRepository extends BaseRepository implements HackathonTeamMemberRepositoryInterface
{
    protected function getModel(): string
    {
        return HackathonTeamMember::class;
    }

    public function getDomainObject(): string
    {
        return HackathonTeamMemberDomainObject::class;
    }

    public function findByTeamId(int $teamId): Collection
    {
        return $this->findWhere([
            HackathonTeamMemberDomainObjectAbstract::TEAM_ID => $teamId,
            HackathonTeamMemberDomainObjectAbstract::STATUS => 'ACTIVE',
        ]);
    }

    public function countActiveByTeamId(int $teamId): int
    {
        return $this->model->newQuery()
            ->where(HackathonTeamMemberDomainObjectAbstract::TEAM_ID, $teamId)
            ->where(HackathonTeamMemberDomainObjectAbstract::STATUS, 'ACTIVE')
            ->count();
    }
}
