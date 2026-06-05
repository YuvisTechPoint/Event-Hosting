<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Hackathon;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Exceptions\ResourceConflictException;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\ResponseCodes;
use HiEvents\Resources\Hackathon\HackathonProjectResource;
use HiEvents\Services\Application\Handlers\Hackathon\CreateHackathonProjectHandler;
use HiEvents\Services\Application\Handlers\Hackathon\DTO\UpsertHackathonProjectDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CreateHackathonProjectAction extends BaseAction
{
    public function __construct(private readonly CreateHackathonProjectHandler $handler)
    {
    }

    public function __invoke(Request $request, int $eventId): JsonResponse
    {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        $request->validate([
            'team_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'repository_url' => 'nullable|url|max:500',
            'demo_url' => 'nullable|url|max:500',
            'tech_stack' => 'nullable|array',
        ]);

        try {
            $project = $this->handler->handle($eventId, new UpsertHackathonProjectDTO(
                team_id: (int) $request->input('team_id'),
                title: $request->input('title'),
                description: $request->input('description'),
                repository_url: $request->input('repository_url'),
                demo_url: $request->input('demo_url'),
                tech_stack: $request->input('tech_stack'),
            ));
        } catch (ResourceConflictException $e) {
            throw ValidationException::withMessages(['team_id' => $e->getMessage()]);
        }

        return $this->resourceResponse(HackathonProjectResource::class, $project, ResponseCodes::HTTP_CREATED);
    }
}
