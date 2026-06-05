<?php

declare(strict_types=1);

namespace HiEvents\Services\Domain\Event;

use HiEvents\DomainObjects\Status\OrderStatus;
use HiEvents\Services\Application\Handlers\Event\DTO\EventAnalyticsResponseDTO;
use HiEvents\Services\Application\Handlers\Event\DTO\EventStatsRequestDTO;
use Illuminate\Config\Repository as Config;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;

readonly class EventAnalyticsFetchService
{
    public function __construct(
        private DatabaseManager $db,
        private EventStatsFetchService $eventStatsFetchService,
        private Cache $cache,
        private Config $config,
    ) {
    }

    public function getAnalytics(EventStatsRequestDTO $requestData): EventAnalyticsResponseDTO
    {
        $cacheTtl = $this->config->get('app.analytics_cache_ttl');
        $cacheKey = 'analytics.event.extended.' . $requestData->event_id . '.' . ($requestData->date_range_preset ?? 'custom');

        if ($cacheTtl) {
            $cached = $this->cache->get($cacheKey);
            if ($cached instanceof EventAnalyticsResponseDTO) {
                return $cached;
            }
        }

        $stats = $this->eventStatsFetchService->getEventStats($requestData);
        $eventId = $requestData->event_id;

        $analytics = new EventAnalyticsResponseDTO(
            revenue_over_time: $stats->daily_stats->map(fn ($row) => [
                'date' => $row->date,
                'revenue' => (float) $row->total_sales_gross,
            ]),
            tickets_by_type: $this->getTicketsByType($eventId),
            check_in_rate_over_time: $this->getCheckInRateOverTime($eventId, $stats->start_date, $stats->end_date),
            geographic_distribution: $this->getGeographicDistribution($eventId),
            conversion_funnel: $this->getConversionFunnel($eventId, (int) $stats->total_views),
            hourly_sales: $this->getHourlySales($eventId),
            refund_summary: $this->getRefundSummary($eventId, (float) $stats->total_refunded, (float) $stats->total_gross_sales),
            repeat_attendees: $this->getRepeatAttendees($eventId),
            start_date: $stats->start_date,
            end_date: $stats->end_date,
        );

        if ($cacheTtl) {
            $this->cache->put($cacheKey, $analytics, $cacheTtl);
        }

        return $analytics;
    }

    public function invalidateCache(int $eventId): void
    {
        foreach (['week', 'month', 'quarter', 'event', 'last_30_days', 'custom'] as $preset) {
            $this->cache->forget('analytics.event.extended.' . $eventId . '.' . $preset);
            $this->cache->forget('analytics.event.' . $eventId . '.' . $preset);
        }
    }

    private function getTicketsByType(int $eventId): Collection
    {
        $rows = $this->db->select(
            <<<SQL
            SELECT
                p.title AS name,
                COALESCE(SUM(oi.quantity), 0)::int AS sold,
                COALESCE(SUM(oi.total_before_additions), 0)::float AS revenue
            FROM order_items oi
            INNER JOIN orders o ON o.id = oi.order_id
            INNER JOIN products p ON p.id = oi.product_id
            WHERE o.event_id = :eventId
              AND o.status = :completed
              AND o.deleted_at IS NULL
            GROUP BY p.id, p.title
            ORDER BY sold DESC
            SQL,
            [
                'eventId' => $eventId,
                'completed' => OrderStatus::COMPLETED->name,
            ]
        );

        return collect($rows)->map(fn (object $row) => [
            'name' => $row->name,
            'sold' => (int) $row->sold,
            'revenue' => (float) $row->revenue,
        ]);
    }

    private function getCheckInRateOverTime(int $eventId, string $startDate, string $endDate): Collection
    {
        $rows = $this->db->select(
            <<<SQL
            SELECT
                a.checked_in_at::date AS date,
                COUNT(*)::int AS checked_in
            FROM attendees a
            INNER JOIN orders o ON o.id = a.order_id
            WHERE o.event_id = :eventId
              AND o.status = :completed
              AND a.checked_in_at IS NOT NULL
              AND a.deleted_at IS NULL
              AND o.deleted_at IS NULL
              AND a.checked_in_at::date BETWEEN :startDate::date AND :endDate::date
            GROUP BY a.checked_in_at::date
            ORDER BY date ASC
            SQL,
            [
                'eventId' => $eventId,
                'completed' => OrderStatus::COMPLETED->name,
                'startDate' => $startDate,
                'endDate' => $endDate,
            ]
        );

        $totalAttendees = (int) ($this->db->selectOne(
            <<<SQL
            SELECT COUNT(*) AS c FROM attendees a
            INNER JOIN orders o ON o.id = a.order_id
            WHERE o.event_id = :eventId AND o.status = :completed
              AND a.deleted_at IS NULL AND o.deleted_at IS NULL
            SQL,
            ['eventId' => $eventId, 'completed' => OrderStatus::COMPLETED->name]
        )->c ?? 0);

        return collect($rows)->map(fn (object $row) => [
            'date' => $row->date,
            'checked_in' => (int) $row->checked_in,
            'total' => $totalAttendees,
            'rate' => $totalAttendees > 0
                ? round(((int) $row->checked_in / $totalAttendees) * 100, 2)
                : 0.0,
        ]);
    }

    private function getGeographicDistribution(int $eventId): Collection
    {
        $rows = $this->db->select(
            <<<SQL
            SELECT
                COALESCE(NULLIF(TRIM(o.locale), ''), 'unknown') AS label,
                COUNT(DISTINCT a.id)::int AS count
            FROM attendees a
            INNER JOIN orders o ON o.id = a.order_id
            WHERE o.event_id = :eventId
              AND o.status = :completed
              AND a.deleted_at IS NULL
              AND o.deleted_at IS NULL
            GROUP BY label
            ORDER BY count DESC
            LIMIT 10
            SQL,
            [
                'eventId' => $eventId,
                'completed' => OrderStatus::COMPLETED->name,
            ]
        );

        if (count($rows) === 0) {
            $eventRow = $this->db->selectOne(
                'SELECT location_details FROM events WHERE id = :eventId',
                ['eventId' => $eventId]
            );
            if ($eventRow?->location_details) {
                $location = json_decode($eventRow->location_details, true);
                $country = $location['country'] ?? $location['city'] ?? null;
                if ($country) {
                    return collect([['label' => strtoupper((string) $country), 'count' => 0]]);
                }
            }
        }

        return collect($rows)->map(fn (object $row) => [
            'label' => $row->label,
            'count' => (int) $row->count,
        ]);
    }

    private function getConversionFunnel(int $eventId, int $pageViews): array
    {
        $counts = $this->db->selectOne(
            <<<SQL
            SELECT
                COUNT(*) FILTER (WHERE status != :cancelled) AS started_checkout,
                COUNT(*) FILTER (WHERE status = :completed) AS completed
            FROM orders
            WHERE event_id = :eventId
              AND deleted_at IS NULL
            SQL,
            [
                'eventId' => $eventId,
                'cancelled' => OrderStatus::CANCELLED->name,
                'completed' => OrderStatus::COMPLETED->name,
            ]
        );

        $started = (int) ($counts->started_checkout ?? 0);
        $completed = (int) ($counts->completed ?? 0);

        return [
            'page_views' => $pageViews,
            'started_checkout' => $started,
            'completed' => $completed,
            'conversion_rate' => $pageViews > 0 ? round(($completed / $pageViews) * 100, 2) : 0.0,
        ];
    }

    private function getHourlySales(int $eventId): Collection
    {
        $rows = $this->db->select(
            <<<SQL
            SELECT
                EXTRACT(HOUR FROM o.created_at)::int AS hour,
                COUNT(*)::int AS sales,
                COALESCE(SUM(o.total_gross), 0)::float AS revenue
            FROM orders o
            WHERE o.event_id = :eventId
              AND o.status = :completed
              AND o.deleted_at IS NULL
            GROUP BY hour
            ORDER BY hour ASC
            SQL,
            [
                'eventId' => $eventId,
                'completed' => OrderStatus::COMPLETED->name,
            ]
        );

        return collect($rows)->map(fn (object $row) => [
            'hour' => (int) $row->hour,
            'sales' => (int) $row->sales,
            'revenue' => (float) $row->revenue,
        ]);
    }

    private function getRefundSummary(int $eventId, float $totalRefunded, float $totalGross): array
    {
        $row = $this->db->selectOne(
            <<<SQL
            SELECT
                COUNT(*) FILTER (WHERE refund_status IS NOT NULL AND refund_status != 'NONE') AS refund_count,
                COALESCE(SUM(total_refunded), 0)::float AS refunded_amount
            FROM orders
            WHERE event_id = :eventId
              AND status = :completed
              AND deleted_at IS NULL
            SQL,
            [
                'eventId' => $eventId,
                'completed' => OrderStatus::COMPLETED->name,
            ]
        );

        $refundCount = (int) ($row->refund_count ?? 0);
        $orderCount = (int) ($this->db->selectOne(
            'SELECT COUNT(*) AS c FROM orders WHERE event_id = :eventId AND status = :completed AND deleted_at IS NULL',
            ['eventId' => $eventId, 'completed' => OrderStatus::COMPLETED->name]
        )->c ?? 0);

        return [
            'refund_count' => $refundCount,
            'refunded_amount' => $totalRefunded ?: (float) ($row->refunded_amount ?? 0),
            'refund_rate' => $orderCount > 0 ? round(($refundCount / $orderCount) * 100, 2) : 0.0,
            'gross_revenue' => $totalGross,
        ];
    }

    private function getRepeatAttendees(int $eventId): array
    {
        $row = $this->db->selectOne(
            <<<SQL
            SELECT
                COUNT(*) AS total_attendees,
                COUNT(*) FILTER (WHERE order_count > 1) AS repeat_attendees
            FROM (
                SELECT LOWER(a.email) AS email, COUNT(DISTINCT a.order_id) AS order_count
                FROM attendees a
                INNER JOIN orders o ON o.id = a.order_id
                WHERE o.event_id = :eventId
                  AND o.status = :completed
                  AND a.deleted_at IS NULL
                  AND o.deleted_at IS NULL
                GROUP BY LOWER(a.email)
            ) AS by_email
            SQL,
            [
                'eventId' => $eventId,
                'completed' => OrderStatus::COMPLETED->name,
            ]
        );

        $total = (int) ($row->total_attendees ?? 0);
        $repeat = (int) ($row->repeat_attendees ?? 0);

        return [
            'total_unique_attendees' => $total,
            'repeat_attendees' => $repeat,
            'repeat_percentage' => $total > 0 ? round(($repeat / $total) * 100, 2) : 0.0,
        ];
    }
}
