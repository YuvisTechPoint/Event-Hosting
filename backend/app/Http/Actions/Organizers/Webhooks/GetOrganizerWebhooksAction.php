<?php

namespace HiEvents\Http\Actions\Organizers\Webhooks;

use HiEvents\DomainObjects\OrganizerDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Resources\Webhook\WebhookResource;
use HiEvents\Services\Application\Handlers\Webhook\GetWebhooksHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetOrganizerWebhooksAction extends BaseAction
{
    public function __construct(
        private readonly GetWebhooksHandler $getWebhooksHandler,
    )
    {
    }

    public function __invoke(int $organizerId, Request $request): JsonResponse
    {
        $this->isActionAuthorized($organizerId, OrganizerDomainObject::class);

        $webhooks = $this->getWebhooksHandler->handler(
            accountId: $this->getAuthenticatedAccountId(),
            params: $this->getPaginationQueryParams($request),
            organizerId: $organizerId,
        );

        return $this->resourceResponse(
            resource: WebhookResource::class,
            data: $webhooks
        );
    }
}
