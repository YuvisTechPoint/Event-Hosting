<?php

declare(strict_types=1);

namespace HiEvents\Repository\Interfaces;

use HiEvents\Models\DeveloperProfile;

interface DeveloperProfileRepositoryInterface
{
    public function findByUserId(int $userId): ?DeveloperProfile;

    public function findByUsername(string $username): ?DeveloperProfile;

    public function findPublicByUsername(string $username): ?DeveloperProfile;
}
