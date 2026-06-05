<?php

declare(strict_types=1);

namespace HiEvents\Resources\Campaign;

use HiEvents\DomainObjects\DripCampaignDomainObject;
use HiEvents\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin DripCampaignDomainObject
 */
class DripCampaignResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'event_id' => $this->getEventId(),
            'name' => $this->getName(),
            'trigger' => $this->getTrigger(),
            'status' => $this->getStatus(),
            'organizer_id' => $this->getOrganizerId(),
            'scheduled_at' => $this->getScheduledAt(),
            'steps' => DripCampaignStepResource::collection($this->getSteps() ?? []),
            'created_at' => $this->getCreatedAt(),
            'updated_at' => $this->getUpdatedAt(),
        ];
    }
}
