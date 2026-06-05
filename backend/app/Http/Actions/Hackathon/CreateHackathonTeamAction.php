<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Hackathon;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Exceptions\ResourceConflictException;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\ResponseCodes;
use HiEvents\Resources\Hackathon\HackathonTeamResource;
use HiEvents\Services\Application\Handlers\Hackathon\CreateHackathonTeamHandler;
use HiEvents\Services\Application\Handlers\Hackathon\DTO\UpsertHackathonTeamDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CreateHackathonTeamAction extends BaseAction
{
    public function __construct(private readonly CreateHackathonTeamHandler $handler)
    {
    }

    public function __invoke(Request $request, int $eventId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'max_members' => 'nullable|integer|min:2|max:20',
        ]);

        try {
            $team = $this->handler->handle(
                $eventId,
                $this->getAuthenticatedUserId(),
                new UpsertHackathonTeamDTO(
                    name: $request->input('name'),
                    description: $request->input('description'),
                    max_members: (int) $request->input('max_members', 4),
                )
            );
        } catch (ResourceConflictException $e) {
            throw ValidationException::withMessages(['name' => $e->getMessage()]);
        }

        return $this->resourceResponse(HackathonTeamResource::class, $team, ResponseCodes::HTTP_CREATED);
    }
}
