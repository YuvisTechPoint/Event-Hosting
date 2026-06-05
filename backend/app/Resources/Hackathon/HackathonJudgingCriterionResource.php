<?php

declare(strict_types=1);

namespace HiEvents\Resources\Hackathon;

use HiEvents\DomainObjects\HackathonJudgingCriterionDomainObject;
use HiEvents\Resources\BaseResource;
use Illuminate\Http\Request;

/** @mixin HackathonJudgingCriterionDomainObject */
class HackathonJudgingCriterionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'event_id' => $this->getEventId(),
            'judging_round_id' => $this->getJudgingRoundId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'max_score' => $this->getMaxScore(),
            'weight' => $this->getWeight(),
            'sort_order' => $this->getSortOrder(),
            'created_at' => $this->getCreatedAt(),
        ];
    }
}
