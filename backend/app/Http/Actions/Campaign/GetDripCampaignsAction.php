<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Campaign;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Resources\Campaign\DripCampaignResource;
use HiEvents\Services\Application\Handlers\Campaign\ListDripCampaignsHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetDripCampaignsAction extends BaseAction
{
    public function __construct(
        private readonly ListDripCampaignsHandler $handler,
    ) {
    }

    public function __invoke(Request $request, int $eventId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $campaigns = $this->handler->handle($eventId, $this->getPaginationQueryParams($request));

        return $this->resourceResponse(
            resource: DripCampaignResource::class,
            data: $campaigns,
        );
    }
}
