<?php

declare(strict_types=1);

namespace HiEvents\Resources\Campaign;

use HiEvents\DomainObjects\DripCampaignStepDomainObject;
use HiEvents\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin DripCampaignStepDomainObject
 */
class DripCampaignStepResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'drip_campaign_id' => $this->getDripCampaignId(),
            'step_order' => $this->getStepOrder(),
            'delay_hours' => $this->getDelayHours(),
            'email_template_id' => $this->getEmailTemplateId(),
            'subject' => $this->getSubject(),
            'body' => $this->getBody(),
            'message_segment_id' => $this->getMessageSegmentId(),
            'created_at' => $this->getCreatedAt(),
            'updated_at' => $this->getUpdatedAt(),
        ];
    }
}
