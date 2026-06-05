<?php

declare(strict_types=1);

namespace HiEvents\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DripCampaignStep extends BaseModel
{
    public function dripCampaign(): BelongsTo
    {
        return $this->belongsTo(DripCampaign::class);
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function messageSegment(): BelongsTo
    {
        return $this->belongsTo(MessageSegment::class);
    }
}
