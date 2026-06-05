<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Hackathon;

use HiEvents\DomainObjects\HackathonProjectDomainObject;
use HiEvents\DomainObjects\Status\HackathonProjectStatus;
use HiEvents\Exceptions\ResourceConflictException;
use HiEvents\Repository\Interfaces\HackathonProjectRepositoryInterface;
use HiEvents\Repository\Interfaces\HackathonTeamRepositoryInterface;
use HiEvents\Services\Application\Handlers\Hackathon\DTO\UpsertHackathonProjectDTO;
use Illuminate\Support\Str;

readonly class CreateHackathonProjectHandler
{
    public function __construct(
        private HackathonProjectRepositoryInterface $projectRepository,
        private HackathonTeamRepositoryInterface $teamRepository,
    ) {
    }

    /**
     * @throws ResourceConflictException
     */
    public function handle(int $eventId, UpsertHackathonProjectDTO $dto): HackathonProjectDomainObject
    {
        $team = $this->teamRepository->findById($dto->team_id);

        if (!$team || $team->getEventId() !== $eventId) {
            throw new ResourceConflictException(__('Team not found for this event'));
        }

        $existing = $this->projectRepository->findFirstWhere(['team_id' => $dto->team_id]);
        if ($existing) {
            throw new ResourceConflictException(__('This team already has a project'));
        }

        $slug = Str::slug($dto->title);

        return $this->projectRepository->create([
            'event_id' => $eventId,
            'team_id' => $dto->team_id,
            'title' => $dto->title,
            'slug' => $slug,
            'description' => $dto->description,
            'repository_url' => $dto->repository_url,
            'demo_url' => $dto->demo_url,
            'tech_stack' => $dto->tech_stack,
            'status' => HackathonProjectStatus::DRAFT->value,
        ]);
    }
}
