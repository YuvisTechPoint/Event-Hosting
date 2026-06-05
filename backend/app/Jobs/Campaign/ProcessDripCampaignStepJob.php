<?php

declare(strict_types=1);

namespace HiEvents\Jobs\Campaign;

use HiEvents\DomainObjects\Enums\MessageTypeEnum;
use HiEvents\Exceptions\AccountNotVerifiedException;
use HiEvents\Exceptions\MessagingTierLimitExceededException;
use HiEvents\Repository\Interfaces\DripCampaignRepositoryInterface;
use HiEvents\Repository\Interfaces\DripCampaignStepRepositoryInterface;
use HiEvents\Repository\Interfaces\EmailTemplateRepositoryInterface;
use HiEvents\Services\Application\Handlers\Message\DTO\SendMessageDTO;
use HiEvents\Services\Application\Handlers\Message\SendMessageHandler;
use HiEvents\Services\Domain\Campaign\MessageSegmentResolverService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDripCampaignStepJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $dripCampaignStepId,
        private readonly int $attendeeId,
        private readonly int $accountId,
        private readonly int $sentByUserId,
        private readonly bool $isTest = false,
    ) {
    }

    /**
     * @throws AccountNotVerifiedException
     * @throws MessagingTierLimitExceededException
     */
    public function handle(
        DripCampaignStepRepositoryInterface $stepRepository,
        DripCampaignRepositoryInterface $campaignRepository,
        EmailTemplateRepositoryInterface $emailTemplateRepository,
        MessageSegmentResolverService $segmentResolver,
        SendMessageHandler $sendMessageHandler,
    ): void {
        $step = $stepRepository->findById($this->dripCampaignStepId);

        if ($step === null) {
            return;
        }

        $campaign = $campaignRepository->findFirstWhere(['id' => $step->getDripCampaignId()]);

        if ($campaign === null) {
            return;
        }

        if (!$this->isTest && $campaign->getStatus() !== 'active') {
            return;
        }

        if (!$segmentResolver->attendeeMatchesSegment(
            $campaign->getEventId(),
            $this->attendeeId,
            $step->getMessageSegmentId(),
        )) {
            return;
        }

        [$subject, $body] = $this->resolveContent($step, $emailTemplateRepository);

        if ($subject === null || $body === null) {
            return;
        }

        $sendMessageHandler->handle(SendMessageDTO::fromArray([
            'account_id' => $this->accountId,
            'event_id' => $campaign->getEventId(),
            'subject' => $subject,
            'message' => $body,
            'type' => MessageTypeEnum::INDIVIDUAL_ATTENDEES->name,
            'is_test' => $this->isTest,
            'send_copy_to_current_user' => false,
            'sent_by_user_id' => $this->sentByUserId,
            'attendee_ids' => [$this->attendeeId],
        ]));
    }

    private function resolveContent($step, EmailTemplateRepositoryInterface $emailTemplateRepository): array
    {
        if ($step->getEmailTemplateId() !== null) {
            $template = $emailTemplateRepository->findById($step->getEmailTemplateId());

            return [$template->getSubject(), $template->getBody()];
        }

        return [$step->getSubject(), $step->getBody()];
    }
}
