<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Application\Handlers\User;

use HiEvents\DomainObjects\UserDomainObject;
use HiEvents\Exceptions\ResourceConflictException;
use HiEvents\Repository\Interfaces\UserRepositoryInterface;
use HiEvents\Services\Application\Handlers\User\ConfirmEmailWithCodeHandler;
use HiEvents\Services\Application\Handlers\User\DTO\ConfirmEmailWithCodeDTO;
use HiEvents\Services\Application\Handlers\User\Exception\InvalidEmailVerificationCodeException;
use HiEvents\Services\Domain\User\VerifyUserEmailService;
use HiEvents\Services\Infrastructure\User\EmailVerificationCodeService;
use Illuminate\Database\DatabaseManager;
use Mockery;
use Tests\TestCase;

class ConfirmEmailWithCodeHandlerTest extends TestCase
{
    private EmailVerificationCodeService $emailVerificationCodeService;
    private UserRepositoryInterface $userRepository;
    private VerifyUserEmailService $verifyUserEmailService;
    private ConfirmEmailWithCodeHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->emailVerificationCodeService = Mockery::mock(EmailVerificationCodeService::class);
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->verifyUserEmailService = Mockery::mock(VerifyUserEmailService::class);
        $databaseManager = Mockery::mock(DatabaseManager::class);
        $databaseManager->shouldReceive('transaction')->andReturnUsing(fn ($callback) => $callback());

        $this->handler = new ConfirmEmailWithCodeHandler(
            $this->emailVerificationCodeService,
            $this->userRepository,
            $databaseManager,
            $this->verifyUserEmailService,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testVerifiesEmailWithValidCode(): void
    {
        $user = new UserDomainObject();
        $user->setId(1);
        $user->setEmail('organizer@example.com');
        $user->setEmailVerifiedAt(null);

        $this->userRepository
            ->shouldReceive('findByIdAndAccountId')
            ->once()
            ->with(1, 10)
            ->andReturn($user);

        $this->emailVerificationCodeService
            ->shouldReceive('verifyCode')
            ->once()
            ->with('organizer@example.com', '12345')
            ->andReturnTrue();

        $this->verifyUserEmailService
            ->shouldReceive('markEmailAsVerified')
            ->once()
            ->with($user, 10);

        $this->handler->handle(new ConfirmEmailWithCodeDTO(
            code: '12345',
            userId: 1,
            accountId: 10,
        ));

        $this->assertTrue(true);
    }

    public function testThrowsWhenVerificationCodeInvalid(): void
    {
        $user = new UserDomainObject();
        $user->setId(1);
        $user->setEmail('organizer@example.com');
        $user->setEmailVerifiedAt(null);

        $this->userRepository
            ->shouldReceive('findByIdAndAccountId')
            ->once()
            ->andReturn($user);

        $this->emailVerificationCodeService
            ->shouldReceive('verifyCode')
            ->once()
            ->andReturnFalse();

        $this->expectException(InvalidEmailVerificationCodeException::class);

        $this->handler->handle(new ConfirmEmailWithCodeDTO(
            code: '00000',
            userId: 1,
            accountId: 10,
        ));
    }

    public function testThrowsWhenEmailAlreadyVerified(): void
    {
        $user = new UserDomainObject();
        $user->setId(1);
        $user->setEmailVerifiedAt(now()->toDateTimeString());

        $this->userRepository
            ->shouldReceive('findByIdAndAccountId')
            ->once()
            ->andReturn($user);

        $this->expectException(ResourceConflictException::class);

        $this->handler->handle(new ConfirmEmailWithCodeDTO(
            code: '12345',
            userId: 1,
            accountId: 10,
        ));
    }
}
