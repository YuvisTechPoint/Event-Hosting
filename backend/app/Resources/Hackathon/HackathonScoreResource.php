<?php

declare(strict_types=1);

namespace HiEvents\Resources\Hackathon;

use HiEvents\DomainObjects\HackathonScoreDomainObject;
use HiEvents\Resources\BaseResource;
use Illuminate\Http\Request;

/** @mixin HackathonScoreDomainObject */
class HackathonScoreResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'criteria_id' => $this->getCriteriaId(),
            'project_id' => $this->getProjectId(),
            'judge_user_id' => $this->getJudgeUserId(),
            'score' => $this->getScore(),
            'feedback' => $this->getFeedback(),
            'created_at' => $this->getCreatedAt(),
            'updated_at' => $this->getUpdatedAt(),
        ];
    }
}
