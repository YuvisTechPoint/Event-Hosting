<?php

declare(strict_types=1);

namespace HiEvents\Services\Application\Handlers\Hackathon;

use HiEvents\DomainObjects\Enums\HackathonTeamMemberRole;
use HiEvents\DomainObjects\HackathonTeamDomainObject;
use HiEvents\DomainObjects\Status\HackathonTeamStatus;
use HiEvents\Exceptions\ResourceConflictException;
use HiEvents\Repository\Interfaces\HackathonTeamMemberRepositoryInterface;
use HiEvents\Repository\Interfaces\HackathonTeamRepositoryInterface;
use HiEvents\Repository\Interfaces\UserRepositoryInterface;
use HiEvents\Services\Application\Handlers\Hackathon\DTO\UpsertHackathonTeamDTO;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;

readonly class CreateHackathonTeamHandler
{
    public function __construct(
        private HackathonTeamRepositoryInterface $teamRepository,
        private HackathonTeamMemberRepositoryInterface $memberRepository,
        private UserRepositoryInterface $userRepository,
        private DatabaseManager $database,
    ) {
    }

    /**
     * @throws ResourceConflictException
     */
    public function handle(int $eventId, int $userId, UpsertHackathonTeamDTO $dto): HackathonTeamDomainObject
    {
        $user = $this->userRepository->findById($userId);
        $slug = Str::slug($dto->name);
        $inviteCode = strtoupper(Str::random(8));

        $existing = $this->teamRepository->findFirstWhere([
            'event_id' => $eventId,
            'slug' => $slug,
        ]);

        if ($existing) {
            throw new ResourceConflictException(__('A team with this name already exists for this event'));
        }

        return $this->database->transaction(function () use ($eventId, $userId, $dto, $slug, $inviteCode, $user) {
            $team = $this->teamRepository->create([
                'event_id' => $eventId,
                'created_by_user_id' => $userId,
                'name' => $dto->name,
                'slug' => $slug,
                'description' => $dto->description,
                'invite_code' => $inviteCode,
                'max_members' => $dto->max_members,
                'status' => HackathonTeamStatus::OPEN->value,
            ]);

            $this->memberRepository->create([
                'team_id' => $team->getId(),
                'user_id' => $userId,
                'email' => $user->getEmail(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
                'role' => HackathonTeamMemberRole::LEADER->value,
                'status' => 'ACTIVE',
                'joined_at' => now(),
            ]);

            return $team;
        });
    }
}
