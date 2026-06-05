<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Campaign;

use HiEvents\DomainObjects\Enums\DripCampaignTrigger;
use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\DomainObjects\Status\DripCampaignStatus;
use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\Request\Campaign\UpsertDripCampaignRequest;
use HiEvents\Resources\Campaign\DripCampaignResource;
use HiEvents\Services\Application\Handlers\Campaign\DTO\UpsertDripCampaignDTO;
use HiEvents\Services\Application\Handlers\Campaign\DTO\UpsertDripCampaignStepDTO;
use HiEvents\Services\Application\Handlers\Campaign\UpdateDripCampaignHandler;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class UpdateDripCampaignAction extends BaseAction
{
    public function __construct(
        private readonly UpdateDripCampaignHandler $handler,
    ) {
    }

    public function __invoke(UpsertDripCampaignRequest $request, int $eventId, int $campaignId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        try {
            $campaign = $this->handler->handle($eventId, $campaignId, $this->buildDto($request));
        } catch (ResourceNotFoundException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        }

        return $this->resourceResponse(DripCampaignResource::class, $campaign);
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
            use_default_template: false,
            scheduled_at: $request->input('scheduled_at'),
            steps: $steps,
        );
    }
}
