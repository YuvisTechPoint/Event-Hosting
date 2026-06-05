<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Campaign;

use HiEvents\DomainObjects\DripCampaignDomainObject;
use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Repository\Interfaces\DripCampaignRepositoryInterface;
use HiEvents\Repository\Interfaces\DripCampaignStepRepositoryInterface;
use HiEvents\Services\Application\Handlers\Campaign\DTO\UpsertDripCampaignDTO;
use HiEvents\Services\Application\Handlers\Campaign\DTO\UpsertDripCampaignStepDTO;
use HiEvents\Services\Infrastructure\HtmlPurifier\HtmlPurifierService;
use Illuminate\Database\DatabaseManager;

class UpdateDripCampaignHandler
{
    public function __construct(
        private readonly DripCampaignRepositoryInterface $dripCampaignRepository,
        private readonly DripCampaignStepRepositoryInterface $dripCampaignStepRepository,
        private readonly HtmlPurifierService $purifier,
        private readonly DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function handle(int $eventId, int $campaignId, UpsertDripCampaignDTO $dto): DripCampaignDomainObject
    {
        $existing = $this->dripCampaignRepository->findByIdAndEventId($campaignId, $eventId);

        if ($existing === null) {
            throw new ResourceNotFoundException(__('Drip campaign not found'));
        }

        return $this->databaseManager->transaction(function () use ($eventId, $campaignId, $dto) {
            $this->dripCampaignRepository->updateFromArray($campaignId, [
                'name' => $dto->name,
                'trigger' => $dto->trigger->value,
                'status' => $dto->status->value,
                'scheduled_at' => $dto->scheduled_at,
            ]);

            if ($dto->steps !== null) {
                $this->dripCampaignStepRepository->deleteByCampaignId($campaignId);

                foreach ($dto->steps as $step) {
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

            return $this->dripCampaignRepository->findByIdAndEventId($campaignId, $eventId);
        });
    }
}
