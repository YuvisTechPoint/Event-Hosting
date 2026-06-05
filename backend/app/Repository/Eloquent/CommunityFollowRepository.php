<?php

declare(strict_types=1);

namespace HiEvents\Repository\Eloquent;

use HiEvents\DomainObjects\Enums\CommunityFollowTargetType;
use HiEvents\Models\CommunityFollow;
use HiEvents\Repository\Interfaces\CommunityFollowRepositoryInterface;

class CommunityFollowRepository implements CommunityFollowRepositoryInterface
{
    public function __construct(
        private readonly CommunityFollow $model,
    ) {
    }

    public function isFollowing(int $followerUserId, CommunityFollowTargetType $targetType, int $targetId): bool
    {
        return $this->model->newQuery()
            ->where('follower_user_id', $followerUserId)
            ->where('target_type', $targetType->value)
            ->where('target_id', $targetId)
            ->exists();
    }

    public function countFollowers(CommunityFollowTargetType $targetType, int $targetId): int
    {
        return $this->model->newQuery()
            ->where('target_type', $targetType->value)
            ->where('target_id', $targetId)
            ->count();
    }

    public function createFollow(int $followerUserId, CommunityFollowTargetType $targetType, int $targetId): CommunityFollow
    {
        return $this->model->newQuery()->create([
            'follower_user_id' => $followerUserId,
            'target_type' => $targetType->value,
            'target_id' => $targetId,
            'created_at' => now(),
        ]);
    }

    public function deleteFollow(int $followerUserId, CommunityFollowTargetType $targetType, int $targetId): bool
    {
        return (bool) $this->model->newQuery()
            ->where('follower_user_id', $followerUserId)
            ->where('target_type', $targetType->value)
            ->where('target_id', $targetId)
            ->delete();
    }
}
