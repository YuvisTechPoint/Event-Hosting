<?php

declare(strict_types=1);

namespace HiEvents\Http\Actions\Campaign;

use HiEvents\DomainObjects\EventDomainObject;
use HiEvents\Exceptions\AccountNotVerifiedException;
use HiEvents\Exceptions\MessagingTierLimitExceededException;
use HiEvents\Exceptions\ResourceNotFoundException;
use HiEvents\Http\Actions\BaseAction;
use HiEvents\Http\Request\Campaign\TriggerDripCampaignTestSendRequest;
use HiEvents\Services\Application\Handlers\Campaign\TriggerDripCampaignTestSendHandler;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TriggerDripCampaignTestSendAction extends BaseAction
{
    public function __construct(
        private readonly TriggerDripCampaignTestSendHandler $handler,
    ) {
    }

    public function __invoke(
        TriggerDripCampaignTestSendRequest $request,
        int $eventId,
        int $campaignId,
    ): JsonResponse {
        $this->isActionAuthorized($eventId, EventDomainObject::class);

        try {
            $this->handler->handle(
                eventId: $eventId,
                campaignId: $campaignId,
                attendeeId: (int) $request->input('attendee_id'),
                accountId: $this->getAuthenticatedAccountId(),
                sentByUserId: $this->getAuthenticatedUser()->getId(),
                stepOrder: $request->input('step_order') !== null ? (int) $request->input('step_order') : null,
            );
        } catch (ResourceNotFoundException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_NOT_FOUND);
        } catch (AccountNotVerifiedException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_UNAUTHORIZED);
        } catch (MessagingTierLimitExceededException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_TOO_MANY_REQUESTS);
        }

        return $this->jsonResponse([
            'message' => __('Test message sent successfully'),
        ]);
    }
}
