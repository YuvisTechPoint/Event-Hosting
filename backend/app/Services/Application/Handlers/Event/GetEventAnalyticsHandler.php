<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Event;

use HiEvents\Services\Application\Handlers\Event\DTO\EventAnalyticsResponseDTO;
use HiEvents\Services\Application\Handlers\Event\DTO\EventStatsRequestDTO;
use HiEvents\Services\Domain\Event\EventAnalyticsFetchService;

readonly class GetEventAnalyticsHandler
{
    public function __construct(private EventAnalyticsFetchService $analyticsFetchService)
    {
    }

    public function handle(EventStatsRequestDTO $requestData): EventAnalyticsResponseDTO
    {
        return $this->analyticsFetchService->getAnalytics($requestData);
    }
}
