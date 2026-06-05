<?php

declare(strict_types=1);

namespace HiEvents\Http\Request\Campaign;

use HiEvents\Http\Request\BaseRequest;

class TriggerDripCampaignTestSendRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'attendee_id' => ['required', 'integer'],
            'step_order' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
