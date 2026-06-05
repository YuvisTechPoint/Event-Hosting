<?php

declare(strict_types=1);

namespace HiEvents\Repository\Interfaces;

use HiEvents\DomainObjects\HackathonTeamMemberDomainObject;
use HiEvents\Repository\Interfaces\RepositoryInterface;
use Illuminate\Support\Collection;

/**
 * @extends RepositoryInterface<HackathonTeamMemberDomainObject>
 */
interface HackathonTeamMemberRepositoryInterface extends RepositoryInterface
{
    public function findByTeamId(int $teamId): Collection;

    public function countActiveByTeamId(int $teamId): int;
}
