<?php

declare(strict_types=1);

namespace HiEvents\Repository\Eloquent;

use HiEvents\DomainObjects\Generated\HackathonTeamDomainObjectAbstract;
use HiEvents\DomainObjects\HackathonTeamDomainObject;
use HiEvents\Http\DTO\QueryParamsDTO;
use HiEvents\Models\HackathonTeam;
use HiEvents\Repository\Interfaces\HackathonTeamRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends BaseRepository<HackathonTeamDomainObject>
 */
class HackathonTeamRepository extends BaseRepository implements HackathonTeamRepositoryInterface
{
    protected function getModel(): string
    {
        return HackathonTeam::class;
    }

    public function getDomainObject(): string
    {
        return HackathonTeamDomainObject::class;
    }

    public function findByEventId(int $eventId, QueryParamsDTO $params): LengthAwarePaginator
    {
        $where = [[HackathonTeamDomainObjectAbstract::EVENT_ID, '=', $eventId]];

        if ($params->query) {
            $where[] = static function (Builder $builder) use ($params) {
                $builder
                    ->where(HackathonTeamDomainObjectAbstract::NAME, 'ilike', '%' . $params->query . '%')
                    ->orWhere(HackathonTeamDomainObjectAbstract::INVITE_CODE, 'ilike', '%' . $params->query . '%');
            };
        }

        return $this->paginateWhere($where, $params->per_page, $params->page);
    }

    public function findByInviteCode(string $inviteCode): ?HackathonTeamDomainObject
    {
        return $this->findFirstWhere([
            HackathonTeamDomainObjectAbstract::INVITE_CODE => strtoupper($inviteCode),
        ]);
    }
}
