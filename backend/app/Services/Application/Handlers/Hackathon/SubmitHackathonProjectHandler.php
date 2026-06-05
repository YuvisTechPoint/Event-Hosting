<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Hackathon;

use HiEvents\DomainObjects\HackathonProjectDomainObject;
use HiEvents\DomainObjects\Status\HackathonProjectStatus;
use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Repository\Interfaces\HackathonProjectRepositoryInterface;

readonly class SubmitHackathonProjectHandler
{
    public function __construct(private HackathonProjectRepositoryInterface $projectRepository)
    {
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function handle(int $eventId, int $projectId): HackathonProjectDomainObject
    {
        $project = $this->projectRepository->findById($projectId);

        if (!$project || $project->getEventId() !== $eventId) {
            throw new ResourceNotFoundException(__('Project not found'));
        }

        return $this->projectRepository->updateFromArray($projectId, [
            'status' => HackathonProjectStatus::SUBMITTED->value,
            'submitted_at' => now(),
        ]);
    }
}
