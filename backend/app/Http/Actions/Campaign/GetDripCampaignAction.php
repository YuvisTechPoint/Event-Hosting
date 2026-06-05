<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Campaign;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Repository\Interfaces\DripCampaignRepositoryInterface;
use HiEvents\Resources\Campaign\DripCampaignResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GetDripCampaignAction extends BaseAction
{
    public function __construct(
        private readonly DripCampaignRepositoryInterface $dripCampaignRepository,
    ) {
    }

    public function __invoke(int $eventId, int $campaignId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $campaign = $this->dripCampaignRepository->findByIdAndEventId($campaignId, $eventId);

        if ($campaign === null) {
            throw new ResourceNotFoundException(__('Drip campaign not found'));
        }

        return $this->resourceResponse(DripCampaignResource::class, $campaign);
    }
}
