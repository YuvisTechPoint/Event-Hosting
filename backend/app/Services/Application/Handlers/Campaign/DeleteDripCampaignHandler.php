<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Campaign;

use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Repository\Interfaces\DripCampaignRepositoryInterface;
use HiEvents\Repository\Interfaces\DripCampaignStepRepositoryInterface;
use Illuminate\Database\DatabaseManager;

class DeleteDripCampaignHandler
{
    public function __construct(
        private readonly DripCampaignRepositoryInterface $dripCampaignRepository,
        private readonly DripCampaignStepRepositoryInterface $dripCampaignStepRepository,
        private readonly DatabaseManager $databaseManager,
    ) {
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function handle(int $eventId, int $campaignId): void
    {
        $existing = $this->dripCampaignRepository->findByIdAndEventId($campaignId, $eventId);

        if ($existing === null) {
            throw new ResourceNotFoundException(__('Drip campaign not found'));
        }

        $this->databaseManager->transaction(function () use ($campaignId) {
            $this->dripCampaignStepRepository->deleteByCampaignId($campaignId);
            $this->dripCampaignRepository->deleteById($campaignId);
        });
    }
}
