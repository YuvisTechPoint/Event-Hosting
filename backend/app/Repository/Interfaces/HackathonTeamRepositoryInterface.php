<?php

declare(strict_types=1);

namespace HiEvents\Repository\Interfaces;

use HiEvents\DomainObjects\HackathonTeamDomainObject;
use HiEvents\Http\DTO\QueryParamsDTO;
use HiEvents\Repository\Interfaces\RepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends RepositoryInterface<HackathonTeamDomainObject>
 */
interface HackathonTeamRepositoryInterface extends RepositoryInterface
{
    public function findByEventId(int $eventId, QueryParamsDTO $params): LengthAwarePaginator;

    public function findByInviteCode(string $inviteCode): ?HackathonTeamDomainObject;
}
