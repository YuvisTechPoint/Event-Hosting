<?php

namespace HiEvents\Services\Application\Handlers\Organizer;

use HiEvents\Repository\DTO\Organizer\OrganizerStatsResponseDTO;
use HiEvents\Repository\Interfaces\OrganizerRepositoryInterface;
use HiEvents\Services\Application\Handlers\Organizer\DTO\GetOrganizerStatsRequestDTO;
use Illuminate\Config\Repository as Config;
use Illuminate\Contracts\Cache\Repository as Cache;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class GetOrganizerStatsHandler
{
    public function __construct(
        private readonly OrganizerRepositoryInterface $repository,
        private readonly Cache $cache,
        private readonly Config $config,
    )
    {
    }

    public function handle(GetOrganizerStatsRequestDTO $statsRequestDTO): OrganizerStatsResponseDTO
    {
        $organizer = $this->repository->findFirstWhere([
            'id' => $statsRequestDTO->organizerId,
            'account_id' => $statsRequestDTO->accountId,
        ]);

        if ($organizer === null) {
            throw new ResourceNotFoundException('Organizer not found');
        }

        $currencyCode = $statsRequestDTO->currencyCode ?? $organizer->getCurrency();
        $cacheTtl = $this->config->get('app.analytics_cache_ttl');
        $cacheKey = 'analytics.organizer.' . $statsRequestDTO->organizerId . '.' . $statsRequestDTO->accountId . '.' . $currencyCode;

        if ($cacheTtl) {
            $cached = $this->cache->get($cacheKey);
            if ($cached instanceof OrganizerStatsResponseDTO) {
                return $cached;
            }
        }

        $stats = $this->repository->getOrganizerStats(
            organizerId: $statsRequestDTO->organizerId,
            accountId: $statsRequestDTO->accountId,
            currencyCode: $currencyCode,
        );

        if ($cacheTtl) {
            $this->cache->put($cacheKey, $stats, $cacheTtl);
        }

        return $stats;
    }
}
