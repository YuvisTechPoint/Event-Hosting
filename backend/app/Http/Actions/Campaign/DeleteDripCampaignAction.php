<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Campaign;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Services\Application\Handlers\Campaign\DeleteDripCampaignHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeleteDripCampaignAction extends BaseAction
{
    public function __construct(
        private readonly DeleteDripCampaignHandler $handler,
    ) {
    }

    public function __invoke(Request $request, int $eventId, int $campaignId): Response
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        try {
            $this->handler->handle($eventId, $campaignId);
        } catch (ResourceNotFoundException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        }

        return $this->deletedResponse();
    }
}
