<?php

declare(strict_types=1);

namespace HiEvents\Resources\Hackathon;

use HiEvents\DomainObjects\HackathonProjectDomainObject;
use HiEvents\Resources\BaseResource;
use Illuminate\Http\Request;

/** @mixin HackathonProjectDomainObject */
class HackathonProjectResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'event_id' => $this->getEventId(),
            'team_id' => $this->getTeamId(),
            'title' => $this->getTitle(),
            'slug' => $this->getSlug(),
            'description' => $this->getDescription(),
            'repository_url' => $this->getRepositoryUrl(),
            'demo_url' => $this->getDemoUrl(),
            'tech_stack' => $this->getTechStack(),
            'status' => $this->getStatus(),
            'submitted_at' => $this->getSubmittedAt(),
            'created_at' => $this->getCreatedAt(),
            'updated_at' => $this->getUpdatedAt(),
        ];
    }
}
