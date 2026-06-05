<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Hackathon;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\ResponseCodes;
use HiEvents\Resources\Hackathon\HackathonJudgingCriterionResource;
use HiEvents\Services\Application\Handlers\Hackathon\CreateHackathonJudgingCriterionHandler;
use HiEvents\Services\Application\Handlers\Hackathon\DTO\UpsertHackathonJudgingCriterionDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreateHackathonJudgingCriterionAction extends BaseAction
{
    public function __construct(private readonly CreateHackathonJudgingCriterionHandler $handler)
    {
    }

    public function __invoke(Request $request, int $eventId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'max_score' => 'nullable|integer|min:1|max:100',
            'weight' => 'nullable|integer|min:1|max:10',
            'judging_round_id' => 'nullable|integer',
        ]);

        $criterion = $this->handler->handle($eventId, new UpsertHackathonJudgingCriterionDTO(
            name: $request->input('name'),
            description: $request->input('description'),
            max_score: (int) $request->input('max_score', 10),
            weight: (int) $request->input('weight', 1),
            judging_round_id: $request->input('judging_round_id') ? (int) $request->input('judging_round_id') : null,
        ));

        return $this->resourceResponse(HackathonJudgingCriterionResource::class, $criterion, ResponseCodes::HTTP_CREATED);
    }
}
