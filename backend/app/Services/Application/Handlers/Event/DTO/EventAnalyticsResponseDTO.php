<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Event\DTO;

use HiEvents\DataTransferObjects\BaseDataObject;
use Illuminate\Support\Collection;

class EventAnalyticsResponseDTO extends BaseDataObject
{
    /**
     * @param Collection<int, array{date: string, revenue: float}> $revenue_over_time
     * @param Collection<int, array{name: string, sold: int, revenue: float}> $tickets_by_type
     * @param Collection<int, array{date: string, checked_in: int, total: int, rate: float}> $check_in_rate_over_time
     * @param Collection<int, array{label: string, count: int}> $geographic_distribution
     * @param Collection<int, array{hour: int, sales: int, revenue: float}> $hourly_sales
     */
    public function __construct(
        public readonly Collection $revenue_over_time,
        public readonly Collection $tickets_by_type,
        public readonly Collection $check_in_rate_over_time,
        public readonly Collection $geographic_distribution,
        public readonly array $conversion_funnel,
        public readonly Collection $hourly_sales,
        public readonly array $refund_summary,
        public readonly array $repeat_attendees,
        public readonly string $start_date,
        public readonly string $end_date,
    ) {
    }
}
