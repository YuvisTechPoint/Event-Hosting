<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Auth;

use HiEvents\Http\Actions\BaseAction;
use HiEvents\Services\Infrastructure\Security\CsrfTokenService;
use Illuminate\Http\Response;

class GetCsrfCookieAction extends BaseAction
{
    public function __construct(
        private readonly CsrfTokenService $csrfTokenService,
    )
    {
    }

    public function __invoke(): Response
    {
        return $this->noContentResponse()
            ->withCookie($this->csrfTokenService->createCookie());
    }
}
