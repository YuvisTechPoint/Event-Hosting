<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Hackathon;

use HiEvents\DomainObjects\HackathonProjectDomainObject;
use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Repository\Interfaces\HackathonProjectRepositoryInterface;
use HiEvents\Services\Application\Handlers\Hackathon\DTO\UpsertHackathonProjectDTO;
use Illuminate\Support\Str;

readonly class UpdateHackathonProjectHandler
{
    public function __construct(private HackathonProjectRepositoryInterface $projectRepository)
    {
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function handle(int $eventId, int $projectId, UpsertHackathonProjectDTO $dto): HackathonProjectDomainObject
    {
        $project = $this->projectRepository->findById($projectId);

        if (!$project || $project->getEventId() !== $eventId) {
            throw new ResourceNotFoundException(__('Project not found'));
        }

        return $this->projectRepository->updateFromArray($projectId, [
            'title' => $dto->title,
            'slug' => Str::slug($dto->title),
            'description' => $dto->description,
            'repository_url' => $dto->repository_url,
            'demo_url' => $dto->demo_url,
            'tech_stack' => $dto->tech_stack,
        ]);
    }
}
