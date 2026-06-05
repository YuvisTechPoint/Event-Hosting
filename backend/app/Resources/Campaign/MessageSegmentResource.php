<?php

declare(strict_types=1);

namespace HiEvents\Resources\Campaign;

use HiEvents\DomainObjects\MessageSegmentDomainObject;
use HiEvents\Resources\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin MessageSegmentDomainObject
 */
class MessageSegmentResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'event_id' => $this->getEventId(),
            'name' => $this->getName(),
            'rules' => $this->getRules(),
            'created_at' => $this->getCreatedAt(),
            'updated_at' => $this->getUpdatedAt(),
        ];
    }
}
