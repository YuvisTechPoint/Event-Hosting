<?php

declare(strict_types=1);

namespace HiEvents\Repository\Eloquent;

use HiEvents\Models\DeveloperProfile;
use HiEvents\Repository\Interfaces\DeveloperProfileRepositoryInterface;

class DeveloperProfileRepository implements DeveloperProfileRepositoryInterface
{
    public function __construct(
        private readonly DeveloperProfile $model,
    ) {
    }

    public function findByUserId(int $userId): ?DeveloperProfile
    {
        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->first();
    }

    public function findByUsername(string $username): ?DeveloperProfile
    {
        return $this->model->newQuery()
            ->where('username', $username)
            ->first();
    }

    public function findPublicByUsername(string $username): ?DeveloperProfile
    {
        return $this->model->newQuery()
            ->where('username', $username)
            ->where('is_public', true)
            ->first();
    }
}
