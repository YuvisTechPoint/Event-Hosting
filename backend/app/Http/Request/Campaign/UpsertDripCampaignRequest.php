<?php

declare(strict_types=1);

namespace HiEvents\Http\Request\Campaign;

use HiEvents\DomainObjects\Enums\DripCampaignTrigger;
use HiEvents\DomainObjects\Status\DripCampaignStatus;
use HiEvents\Http\Request\BaseRequest;
use Illuminate\Validation\Rule;

class UpsertDripCampaignRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'trigger' => ['required', Rule::in(DripCampaignTrigger::valuesArray())],
            'status' => ['sometimes', Rule::in(DripCampaignStatus::valuesArray())],
            'use_default_template' => ['sometimes', 'boolean'],
            'scheduled_at' => ['nullable', 'date'],
            'steps' => ['sometimes', 'array'],
            'steps.*.step_order' => ['required_with:steps', 'integer', 'min:1'],
            'steps.*.delay_hours' => ['required_with:steps', 'integer', 'min:0'],
            'steps.*.email_template_id' => ['nullable', 'integer', 'exists:email_templates,id'],
            'steps.*.subject' => ['nullable', 'string', 'max:255'],
            'steps.*.body' => ['nullable', 'string'],
            'steps.*.message_segment_id' => ['nullable', 'integer', 'exists:message_segments,id'],
        ];
    }
}
