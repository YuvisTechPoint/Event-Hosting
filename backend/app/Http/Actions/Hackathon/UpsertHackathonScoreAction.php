<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Hackathon;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\ResponseCodes;
use HiEvents\Resources\Hackathon\HackathonScoreResource;
use HiEvents\Services\Application\Handlers\Hackathon\DTO\UpsertHackathonScoreDTO;
use HiEvents\Services\Application\Handlers\Hackathon\UpsertHackathonScoreHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpsertHackathonScoreAction extends BaseAction
{
    public function __construct(private readonly UpsertHackathonScoreHandler $handler)
    {
    }

    public function __invoke(Request $request, int $eventId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $request->validate([
            'criteria_id' => 'required|integer',
            'project_id' => 'required|integer',
            'score' => 'required|numeric|min:0',
            'feedback' => 'nullable|string',
        ]);

        $score = $this->handler->handle(
            $this->getAuthenticatedUserId(),
            new UpsertHackathonScoreDTO(
                criteria_id: (int) $request->input('criteria_id'),
                project_id: (int) $request->input('project_id'),
                score: (float) $request->input('score'),
                feedback: $request->input('feedback'),
            )
        );

        return $this->resourceResponse(HackathonScoreResource::class, $score, ResponseCodes::HTTP_CREATED);
    }
}
