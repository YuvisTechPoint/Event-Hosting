<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Campaign;

use HiEvents\DomainObjects\Enums\DripCampaignTrigger;
use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\DomainObjects\Status\DripCampaignStatus;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\Request\Campaign\UpsertDripCampaignRequest;
use HiEvents\Http\ResponseCodes;
use HiEvents\Resources\Campaign\DripCampaignResource;
use HiEvents\Services\Application\Handlers\Campaign\CreateDripCampaignHandler;
use HiEvents\Services\Application\Handlers\Campaign\DTO\UpsertDripCampaignDTO;
use HiEvents\Services\Application\Handlers\Campaign\DTO\UpsertDripCampaignStepDTO;
use Illuminate\Http\JsonResponse;

class CreateDripCampaignAction extends BaseAction
{
    public function __construct(
        private readonly CreateDripCampaignHandler $handler,
    ) {
    }

    public function __invoke(UpsertDripCampaignRequest $request, int $eventId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $campaign = $this->handler->handle($eventId, $this->buildDto($request));

        return $this->resourceResponse(
            resource: DripCampaignResource::class,
            data: $campaign,
            statusCode: ResponseCodes::HTTP_CREATED,
        );
    }

    private function buildDto(UpsertDripCampaignRequest $request): UpsertDripCampaignDTO
    {
        $steps = null;

        if ($request->has('steps')) {
            $steps = array_map(
                static fn(array $step) => UpsertDripCampaignStepDTO::fromArray($step),
                $request->input('steps', []),
            );
        }

        return new UpsertDripCampaignDTO(
            name: $request->input('name'),
            trigger: DripCampaignTrigger::from($request->input('trigger')),
            status: DripCampaignStatus::from($request->input('status', DripCampaignStatus::DRAFT->value)),
            use_default_template: $request->boolean('use_default_template', true),
            scheduled_at: $request->input('scheduled_at'),
            steps: $steps,
        );
    }
}
