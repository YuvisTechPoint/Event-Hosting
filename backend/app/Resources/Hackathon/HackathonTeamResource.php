<?php

declare(strict_types=1);

namespace HiEvents\Resources\Hackathon;

use HiEvents\DomainObjects\HackathonTeamDomainObject;
use HiEvents\Resources\BaseResource;
use Illuminate\Http\Request;

/** @mixin HackathonTeamDomainObject */
class HackathonTeamResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'event_id' => $this->getEventId(),
            'name' => $this->getName(),
            'slug' => $this->getSlug(),
            'description' => $this->getDescription(),
            'invite_code' => $this->getInviteCode(),
            'max_members' => $this->getMaxMembers(),
            'status' => $this->getStatus(),
            'created_by_user_id' => $this->getCreatedByUserId(),
            'created_at' => $this->getCreatedAt(),
            'updated_at' => $this->getUpdatedAt(),
        ];
    }
}
