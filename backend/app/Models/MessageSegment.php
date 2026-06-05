<?php

declare(strict_types=1);

namespace HiEvents\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageSegment extends BaseModel
{
    protected function getCastMap(): array
    {
        return [
            'rules' => 'array',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function dripCampaignSteps(): HasMany
    {
        return $this->hasMany(DripCampaignStep::class);
    }
}
