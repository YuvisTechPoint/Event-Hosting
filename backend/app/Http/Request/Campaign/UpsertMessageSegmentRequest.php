<?php

declare(strict_types=1);

namespace HiEvents\Http\Request\Campaign;

use HiEvents\Http\Request\BaseRequest;

class UpsertMessageSegmentRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'rules' => ['sometimes', 'array'],
            'rules.ticket_type_ids' => ['sometimes', 'array'],
            'rules.ticket_type_ids.*' => ['integer'],
            'rules.check_in_status' => ['sometimes', 'nullable', 'string', 'in:checked_in,not_checked_in'],
            'rules.tags' => ['sometimes', 'array'],
            'rules.registration_status' => ['sometimes', 'array'],
            'rules.registration_status.*' => ['string'],
        ];
    }
}
