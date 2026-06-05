<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Events;

use HiEvents\Http\Actions\BaseAction;
use HiEvents\Resources\Event\EventResourcePublic;
use HiEvents\Services\Application\Handlers\Event\BrowsePublicEventsHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetBrowseEventsPublicAction extends BaseAction
{
    public function __construct(private readonly BrowsePublicEventsHandler $handler)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $events = $this->handler->handle($request);

        return $this->resourceResponse(
            resource: EventResourcePublic::class,
            data: $events,
        );
    }
}
