<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Events\Stats;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Services\Application\Handlers\Event\DTO\EventStatsRequestDTO;
use HiEvents\Services\Application\Handlers\Event\GetEventAnalyticsHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GetEventAnalyticsAction extends BaseAction
{
    public function __construct(
        private readonly GetEventAnalyticsHandler $analyticsHandler,
    ) {
    }

    public function __invoke(int $eventId, Request $request): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $analytics = $this->analyticsHandler->handle(EventStatsRequestDTO::fromArray([
            'event_id' => $eventId,
            'date_range_preset' => $request->query('date_range', 'month'),
        ]));

        return $this->resourceResponse(JsonResource::class, $analytics);
    }
}
