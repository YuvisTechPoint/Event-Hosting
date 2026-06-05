<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Campaign;

use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Jobs\Campaign\ProcessDripCampaignStepJob;
use HiEvents\Repository\Interfaces\AttendeeRepositoryInterface;
use HiEvents\Repository\Interfaces\DripCampaignRepositoryInterface;
use HiEvents\Repository\Interfaces\DripCampaignStepRepositoryInterface;

class TriggerDripCampaignTestSendHandler
{
    public function __construct(
        private readonly DripCampaignRepositoryInterface $dripCampaignRepository,
        private readonly DripCampaignStepRepositoryInterface $dripCampaignStepRepository,
        private readonly AttendeeRepositoryInterface $attendeeRepository,
    ) {
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function handle(
        int $eventId,
        int $campaignId,
        int $attendeeId,
        int $accountId,
        int $sentByUserId,
        ?int $stepOrder = null,
    ): void {
        $campaign = $this->dripCampaignRepository->findByIdAndEventId($campaignId, $eventId);

        if ($campaign === null) {
            throw new ResourceNotFoundException(__('Drip campaign not found'));
        }

        $attendee = $this->attendeeRepository->findFirstWhere([
            'id' => $attendeeId,
            'event_id' => $eventId,
        ]);

        if ($attendee === null) {
            throw new ResourceNotFoundException(__('Attendee not found'));
        }

        $steps = $this->dripCampaignStepRepository->findByCampaignId($campaignId);

        if ($steps->isEmpty()) {
            throw new ResourceNotFoundException(__('Drip campaign has no steps'));
        }

        $step = $stepOrder !== null
            ? $steps->first(fn($s) => $s->getStepOrder() === $stepOrder)
            : $steps->first();

        if ($step === null) {
            throw new ResourceNotFoundException(__('Drip campaign step not found'));
        }

        ProcessDripCampaignStepJob::dispatchSync(
            dripCampaignStepId: $step->getId(),
            attendeeId: $attendeeId,
            accountId: $accountId,
            sentByUserId: $sentByUserId,
            isTest: true,
        );
    }
}
