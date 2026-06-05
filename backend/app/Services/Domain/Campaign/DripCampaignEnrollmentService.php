<?php

declare(strict_types=1);

namespace HiEvents\Services\Domain\Campaign;

use HiEvents\DomainObjects\AttendeeDomainObject;
use HiEvents\DomainObjects\Enums\DripCampaignTrigger;
use HiEvents\Jobs\Campaign\ProcessDripCampaignStepJob;
use HiEvents\Repository\Interfaces\AttendeeRepositoryInterface;
use HiEvents\Repository\Interfaces\DripCampaignRepositoryInterface;
use Illuminate\Support\Collection;

class DripCampaignEnrollmentService
{
    // Scheduled trigger (DripCampaignTrigger::SCHEDULED) is deferred — only ON_REGISTRATION is wired via listener.
    public function __construct(
        private readonly DripCampaignRepositoryInterface $dripCampaignRepository,
        private readonly AttendeeRepositoryInterface $attendeeRepository,
        private readonly MessageSegmentResolverService $segmentResolver,
    ) {
    }

    public function enrollAttendeesFromOrder(int $eventId, int $orderId, int $accountId, int $sentByUserId): void
    {
        $campaigns = $this->dripCampaignRepository->findActiveByEventIdAndTrigger(
            $eventId,
            DripCampaignTrigger::ON_REGISTRATION->value,
        );

        if ($campaigns->isEmpty()) {
            return;
        }

        $attendees = $this->attendeeRepository->findWhere([
            'event_id' => $eventId,
            'order_id' => $orderId,
        ]);

        foreach ($campaigns as $campaign) {
            $this->scheduleCampaignForAttendees($campaign, $attendees, $accountId, $sentByUserId);
        }
    }

    private function scheduleCampaignForAttendees(
        $campaign,
        Collection $attendees,
        int $accountId,
        int $sentByUserId,
    ): void {
        $steps = $campaign->getSteps() ?? [];

        foreach ($attendees as $attendee) {
            /** @var AttendeeDomainObject $attendee */
            foreach ($steps as $step) {
                if (!$this->segmentResolver->attendeeMatchesSegment(
                    $campaign->getEventId(),
                    $attendee->getId(),
                    $step->getMessageSegmentId(),
                )) {
                    continue;
                }

                ProcessDripCampaignStepJob::dispatch(
                    dripCampaignStepId: $step->getId(),
                    attendeeId: $attendee->getId(),
                    accountId: $accountId,
                    sentByUserId: $sentByUserId,
                    isTest: false,
                )->delay(now()->addHours($step->getDelayHours()));
            }
        }
    }
}
