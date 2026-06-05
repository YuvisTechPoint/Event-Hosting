<?php

declare(strict_types=1);

namespace HiEvents\Services\Infrastructure\Broadcasting;

/*
| Real-time broadcast event catalog (Section 6.1)
|------------------------------------------------------------------------------
| Event class                  | broadcastAs()        | Channels
| ---------------------------- | -------------------- | ------------------------------------------
| AttendeeCheckedInEvent       | attendee.checked-in  | private event.{id}, public event.{id}.check-in
| AttendeeRegisteredEvent      | attendee.registered  | private event.{id}
| TicketSoldEvent              | ticket.sold          | private event.{id}, public event.{id}.capacity
| EventCapacityWarningEvent    | capacity.warning     | private event.{id}, public event.{id}.capacity
|                              | capacity.sold-out    | (same channels, level-dependent name)
| OrganizerNotificationEvent   | notification.new     | private event.{id}
|
| Dispatched from: CheckInAttendeeHandler, CreateAttendeeCheckInService (check-in),
| CompleteOrderHandler, PaymentIntentSucceededHandler (order completed),
| RefundOrderHandler, ChargeRefundUpdatedHandler (refunds), SendMessageHandler (messages).
| All events use ShouldBroadcastNow and scalar/enum payloads (no Eloquent models).
| Public capacity/check-in channels are unauthenticated; private event.{id} requires JWT.
*/

use Carbon\Carbon;
use HiEvents\DomainObjects\AttendeeDomainObject;
use HiEvents\DomainObjects\Enums\CapacityWarningLevel;
use HiEvents\DomainObjects\Enums\OrganizerNotificationType;
use HiEvents\DomainObjects\Enums\ProductType;
use HiEvents\DomainObjects\Generated\AttendeeDomainObjectAbstract;
use HiEvents\DomainObjects\OrderDomainObject;
use HiEvents\DomainObjects\OrderItemDomainObject;
use HiEvents\Events\Broadcasting\AttendeeCheckedInEvent;
use HiEvents\Events\Broadcasting\AttendeeRegisteredEvent;
use HiEvents\Events\Broadcasting\EventCapacityWarningEvent;
use HiEvents\Events\Broadcasting\OrganizerNotificationEvent;
use HiEvents\Events\Broadcasting\TicketSoldEvent;
use HiEvents\Repository\Interfaces\AttendeeRepositoryInterface;
use HiEvents\Repository\Interfaces\ProductRepositoryInterface;
use HiEvents\Services\Domain\Event\EventStatsFetchService;
use HiEvents\Services\Domain\Product\AvailableProductQuantitiesFetchService;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\DatabaseManager;

readonly class EventRealtimeBroadcastService
{
    private const CAPACITY_WARNING_CACHE_PREFIX = 'capacity_warning:event:';

    public function __construct(
        private AttendeeRepositoryInterface              $attendeeRepository,
        private ProductRepositoryInterface               $productRepository,
        private EventStatsFetchService                   $eventStatsFetchService,
        private AvailableProductQuantitiesFetchService   $availableProductQuantitiesFetchService,
        private DatabaseManager                          $db,
        private CacheRepository                          $cache,
    )
    {
    }

    public function broadcastAttendeeCheckedIn(
        AttendeeDomainObject $attendee,
        ?string              $checkedInBy,
        string               $checkedInAt,
    ): void
    {
        $stats = $this->eventStatsFetchService->getCheckedInStats($attendee->getEventId());
        $capacity = $this->getEventCapacityStats($attendee->getEventId());

        event(new AttendeeCheckedInEvent(
            attendeeId: $attendee->getId(),
            attendeePublicId: $attendee->getPublicId(),
            attendeeName: $attendee->getFullName(),
            ticketType: $this->resolveTicketType($attendee),
            checkedInBy: $checkedInBy,
            checkedInAt: $checkedInAt,
            eventId: $attendee->getEventId(),
            totalCheckedIn: $stats->total_checked_in_attendees,
            totalCapacity: $capacity['total_capacity'],
        ));

        event(new OrganizerNotificationEvent(
            eventId: $attendee->getEventId(),
            type: OrganizerNotificationType::CHECK_IN,
            title: __('Check-in completed'),
            message: __(':name checked in for :ticket', [
                'name' => $attendee->getFullName(),
                'ticket' => $this->resolveTicketType($attendee),
            ]),
            createdAt: $checkedInAt,
            metadata: [
                'attendee_id' => $attendee->getId(),
                'checked_in_by' => $checkedInBy,
            ],
        ));
    }

    public function handleOrderCompleted(OrderDomainObject $order): void
    {
        $this->broadcastTicketSales($order);
        $this->broadcastAttendeeRegistrations($order);
        $this->broadcastPurchaseNotification($order);
        $this->checkAndBroadcastCapacityWarnings($order->getEventId());
    }

    public function broadcastRefundNotification(
        OrderDomainObject $order,
        float             $amount,
        string            $currency,
    ): void
    {
        event(new OrganizerNotificationEvent(
            eventId: $order->getEventId(),
            type: OrganizerNotificationType::REFUND,
            title: __('Refund processed'),
            message: __('A refund of :amount was processed for order #:id', [
                'amount' => $amount . ' ' . strtoupper($currency),
                'id' => $order->getPublicId(),
            ]),
            createdAt: Carbon::now()->toDateTimeString(),
            metadata: [
                'order_id' => $order->getId(),
                'amount' => $amount,
                'currency' => $currency,
            ],
        ));
    }

    public function broadcastRefundRequestedNotification(OrderDomainObject $order, float $amount, string $currency): void
    {
        event(new OrganizerNotificationEvent(
            eventId: $order->getEventId(),
            type: OrganizerNotificationType::REFUND,
            title: __('Refund requested'),
            message: __('A refund of :amount was requested for order #:id', [
                'amount' => $amount . ' ' . strtoupper($currency),
                'id' => $order->getPublicId(),
            ]),
            createdAt: Carbon::now()->toDateTimeString(),
            metadata: [
                'order_id' => $order->getId(),
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'pending',
            ],
        ));
    }

    public function broadcastMessageNotification(int $eventId, string $subject, int $messageId): void
    {
        event(new OrganizerNotificationEvent(
            eventId: $eventId,
            type: OrganizerNotificationType::MESSAGE,
            title: __('Message sent'),
            message: $subject,
            createdAt: Carbon::now()->toDateTimeString(),
            metadata: [
                'message_id' => $messageId,
            ],
        ));
    }

    public function checkAndBroadcastCapacityWarnings(int $eventId): void
    {
        $capacity = $this->getEventCapacityStats($eventId);

        if ($capacity['total_capacity'] === null || $capacity['total_capacity'] === 0) {
            return;
        }

        $percentFull = ($capacity['total_sold'] / $capacity['total_capacity']) * 100;

        if ($percentFull >= 100 && !$this->hasCapacityWarningBeenSent($eventId, CapacityWarningLevel::SOLD_OUT)) {
            $this->markCapacityWarningSent($eventId, CapacityWarningLevel::SOLD_OUT);
            event(new EventCapacityWarningEvent(
                eventId: $eventId,
                level: CapacityWarningLevel::SOLD_OUT,
                totalSold: $capacity['total_sold'],
                totalCapacity: $capacity['total_capacity'],
                percentFull: round($percentFull, 1),
            ));

            return;
        }

        if ($percentFull >= 80 && !$this->hasCapacityWarningBeenSent($eventId, CapacityWarningLevel::WARNING)) {
            $this->markCapacityWarningSent($eventId, CapacityWarningLevel::WARNING);
            event(new EventCapacityWarningEvent(
                eventId: $eventId,
                level: CapacityWarningLevel::WARNING,
                totalSold: $capacity['total_sold'],
                totalCapacity: $capacity['total_capacity'],
                percentFull: round($percentFull, 1),
            ));
        }
    }

    private function broadcastTicketSales(OrderDomainObject $order): void
    {
        $eventCapacity = $this->getEventCapacityStats($order->getEventId());
        $availableQuantities = $this->availableProductQuantitiesFetchService
            ->getAvailableProductQuantities($order->getEventId(), ignoreCache: true);

        foreach ($order->getOrderItems() ?? [] as $orderItem) {
            if ($orderItem->getProductType() !== ProductType::TICKET->name) {
                continue;
            }

            $productQuantity = $availableQuantities->productQuantities
                ->first(fn($q) => $q->price_id === $orderItem->getProductPriceId());

            event(new TicketSoldEvent(
                eventId: $order->getEventId(),
                productId: $orderItem->getProductId(),
                productTitle: $orderItem->getItemName() ?? $productQuantity?->product_title ?? '',
                quantitySold: $orderItem->getQuantity(),
                remainingCapacity: $productQuantity?->quantity_available ?? 0,
                isUnlimited: $productQuantity?->initial_quantity_available === null,
                totalEventSold: $eventCapacity['total_sold'],
                totalEventCapacity: $eventCapacity['total_capacity'],
            ));
        }
    }

    private function broadcastAttendeeRegistrations(OrderDomainObject $order): void
    {
        $attendees = $this->attendeeRepository->findWhere([
            AttendeeDomainObjectAbstract::ORDER_ID => $order->getId(),
            AttendeeDomainObjectAbstract::EVENT_ID => $order->getEventId(),
        ]);

        foreach ($attendees as $attendee) {
            event(new AttendeeRegisteredEvent(
                attendeeId: $attendee->getId(),
                attendeeName: $this->formatRegistrationDisplayName($attendee),
                ticketType: $this->resolveTicketType($attendee),
                registeredAt: $attendee->getCreatedAt() ?? Carbon::now()->toDateTimeString(),
                eventId: $attendee->getEventId(),
            ));
        }
    }

    private function broadcastPurchaseNotification(OrderDomainObject $order): void
    {
        $ticketCount = $order->getOrderItems()
            ?->filter(fn(OrderItemDomainObject $item) => $item->getProductType() === ProductType::TICKET->name)
            ?->sum(fn(OrderItemDomainObject $item) => $item->getQuantity()) ?? 0;

        event(new OrganizerNotificationEvent(
            eventId: $order->getEventId(),
            type: OrganizerNotificationType::PURCHASE,
            title: __('New ticket purchase'),
            message: __(':count ticket(s) purchased in order #:id', [
                'count' => $ticketCount,
                'id' => $order->getPublicId(),
            ]),
            createdAt: Carbon::now()->toDateTimeString(),
            metadata: [
                'order_id' => $order->getId(),
                'buyer_email' => $order->getEmail(),
                'total_gross' => $order->getTotalGross(),
            ],
        ));
    }

    private function resolveTicketType(AttendeeDomainObject $attendee): string
    {
        if ($attendee->getProduct() !== null) {
            return $attendee->getProduct()->getTitle();
        }

        $product = $this->productRepository->findById($attendee->getProductId());

        return $product?->getTitle() ?? __('Ticket');
    }

    private function formatRegistrationDisplayName(AttendeeDomainObject $attendee): string
    {
        $lastInitial = $attendee->getLastName()
            ? mb_substr($attendee->getLastName(), 0, 1) . '.'
            : '';

        return trim($attendee->getFirstName() . ' ' . $lastInitial);
    }

    /**
     * @return array{total_sold: int, total_capacity: ?int}
     */
    private function getEventCapacityStats(int $eventId): array
    {
        $result = $this->db->selectOne(<<<SQL
            SELECT
                COALESCE(SUM(product_prices.quantity_sold), 0) AS total_sold,
                SUM(product_prices.initial_quantity_available) AS total_capacity,
                BOOL_OR(product_prices.initial_quantity_available IS NULL) AS has_unlimited
            FROM products
            INNER JOIN product_prices ON product_prices.product_id = products.id
            WHERE products.event_id = :eventId
              AND products.product_type = :ticketType
              AND products.deleted_at IS NULL
              AND product_prices.deleted_at IS NULL
        SQL, [
            'eventId' => $eventId,
            'ticketType' => ProductType::TICKET->name,
        ]);

        $hasUnlimited = (bool) ($result->has_unlimited ?? false);
        $totalCapacity = $result->total_capacity !== null ? (int) $result->total_capacity : null;

        return [
            'total_sold' => (int) ($result->total_sold ?? 0),
            'total_capacity' => $hasUnlimited ? null : $totalCapacity,
        ];
    }

    private function hasCapacityWarningBeenSent(int $eventId, CapacityWarningLevel $level): bool
    {
        return $this->cache->has(self::CAPACITY_WARNING_CACHE_PREFIX . $eventId . ':' . $level->value);
    }

    private function markCapacityWarningSent(int $eventId, CapacityWarningLevel $level): void
    {
        $this->cache->put(
            self::CAPACITY_WARNING_CACHE_PREFIX . $eventId . ':' . $level->value,
            true,
            now()->addDays(30),
        );
    }
}
