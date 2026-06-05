<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Campaign;

use HiEvents\DomainObjects\DripCampaignDomainObject;
use HiEvents\DomainObjects\Enums\DripCampaignTrigger;
use HiEvents\DomainObjects\Status\DripCampaignStatus;
use HiEvents\Repository\Interfaces\DripCampaignRepositoryInterface;
use HiEvents\Repository\Interfaces\DripCampaignStepRepositoryInterface;
use HiEvents\Repository\Interfaces\EventRepositoryInterface;
use HiEvents\Services\Application\Handlers\Campaign\DTO\UpsertDripCampaignDTO;
use HiEvents\Services\Application\Handlers\Campaign\DTO\UpsertDripCampaignStepDTO;
use HiEvents\Services\Infrastructure\HtmlPurifier\HtmlPurifierService;
use Illuminate\Database\DatabaseManager;

class CreateDripCampaignHandler
{
    public function __construct(
        private readonly DripCampaignRepositoryInterface $dripCampaignRepository,
        private readonly DripCampaignStepRepositoryInterface $dripCampaignStepRepository,
        private readonly EventRepositoryInterface $eventRepository,
        private readonly HtmlPurifierService $purifier,
        private readonly DatabaseManager $databaseManager,
    ) {
    }

    public function handle(int $eventId, UpsertDripCampaignDTO $dto): DripCampaignDomainObject
    {
        return $this->databaseManager->transaction(function () use ($eventId, $dto) {
            $event = $this->eventRepository->findById($eventId);

            $campaign = $this->dripCampaignRepository->create([
                'event_id' => $eventId,
                'name' => $dto->name,
                'trigger' => $dto->trigger->value,
                'status' => $dto->status->value,
                'organizer_id' => $event->getOrganizerId(),
                'scheduled_at' => $dto->scheduled_at,
            ]);

            $steps = $dto->steps ?? ($dto->use_default_template ? $this->defaultSteps() : []);

            foreach ($steps as $step) {
                $this->createStep($campaign->getId(), $step);
            }

            return $this->dripCampaignRepository->findByIdAndEventId($campaign->getId(), $eventId);
        });
    }

    /** @return UpsertDripCampaignStepDTO[] */
    private function defaultSteps(): array
    {
        return [
            new UpsertDripCampaignStepDTO(
                step_order: 1,
                delay_hours: 0,
                subject: __('Registration confirmed'),
                body: __('<p>Thank you for registering! We are excited to see you at the event.</p>'),
            ),
            new UpsertDripCampaignStepDTO(
                step_order: 2,
                delay_hours: 24,
                subject: __('Event reminder'),
                body: __('<p>This is a friendly reminder about your upcoming event. We look forward to seeing you there!</p>'),
            ),
            new UpsertDripCampaignStepDTO(
                step_order: 3,
                delay_hours: 48,
                subject: __('Thank you for attending'),
                body: __('<p>Thank you for being part of our event. We hope you had a great experience!</p>'),
            ),
        ];
    }

    private function createStep(int $campaignId, UpsertDripCampaignStepDTO $step): void
    {
        $this->dripCampaignStepRepository->create([
            'drip_campaign_id' => $campaignId,
            'step_order' => $step->step_order,
            'delay_hours' => $step->delay_hours,
            'email_template_id' => $step->email_template_id,
            'subject' => $step->subject,
            'body' => $step->body !== null ? $this->purifier->purify($step->body) : null,
            'message_segment_id' => $step->message_segment_id,
        ]);
    }
}
