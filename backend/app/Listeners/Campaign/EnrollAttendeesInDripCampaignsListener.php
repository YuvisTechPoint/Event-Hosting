<?php

declare(strict_types=1);

namespace HiEvents\Listeners\Campaign;

use HiEvents\DomainObjects\Status\OrderStatus;
use HiEvents\Events\OrderStatusChangedEvent;
use HiEvents\Repository\Interfaces\AccountUserRepositoryInterface;
use HiEvents\Repository\Interfaces\EventRepositoryInterface;
use HiEvents\Services\Domain\Campaign\DripCampaignEnrollmentService;

class EnrollAttendeesInDripCampaignsListener
{
    public function __construct(
        private readonly DripCampaignEnrollmentService $enrollmentService,
        private readonly EventRepositoryInterface $eventRepository,
        private readonly AccountUserRepositoryInterface $accountUserRepository,
    ) {
    }

    public function handle(OrderStatusChangedEvent $event): void
    {
        $order = $event->order;

        if ($order->getStatus() !== OrderStatus::COMPLETED->name) {
            return;
        }

        $eventDomainObject = $this->eventRepository->findById($order->getEventId());
        $accountUser = $this->accountUserRepository->findFirstWhere([
            'account_id' => $eventDomainObject->getAccountId(),
        ]);

        if ($accountUser === null) {
            return;
        }

        $this->enrollmentService->enrollAttendeesFromOrder(
            eventId: $order->getEventId(),
            orderId: $order->getId(),
            accountId: $eventDomainObject->getAccountId(),
            sentByUserId: $accountUser->getUserId(),
        );
    }
}
