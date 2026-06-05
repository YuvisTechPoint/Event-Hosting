<?php

namespace HiEvents\Http\Actions\Webhooks;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Resources\Webhook\WebhookResource;
use HiEvents\Services\Application\Handlers\Webhook\GetWebhooksHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetWebhooksAction extends BaseAction
{
    public function __construct(
        private readonly GetWebhooksHandler $getWebhooksHandler,
    )
    {
    }

    public function __invoke(int $eventId, Request $request): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $webhooks = $this->getWebhooksHandler->handler(
            accountId: $this->getAuthenticatedAccountId(),
            params: $this->getPaginationQueryParams($request),
            eventId: $eventId,
        );

        return $this->resourceResponse(
            resource: WebhookResource::class,
            data: $webhooks
        );
    }
}
