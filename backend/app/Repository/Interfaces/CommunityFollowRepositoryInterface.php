<?php

declare(strict_types=1);

namespace HiEvents\Repository\Interfaces;

use HiEvents\DomainObjects\Enums\CommunityFollowTargetType;
use HiEvents\Models\CommunityFollow;

interface CommunityFollowRepositoryInterface
{
    public function isFollowing(int $followerUserId, CommunityFollowTargetType $targetType, int $targetId): bool;

    public function countFollowers(CommunityFollowTargetType $targetType, int $targetId): int;

    public function createFollow(int $followerUserId, CommunityFollowTargetType $targetType, int $targetId): CommunityFollow;

    public function deleteFollow(int $followerUserId, CommunityFollowTargetType $targetType, int $targetId): bool;
}
