<?php

declare(strict_types=1);

namespace HiEvents\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DripCampaign extends BaseModel
{
    protected function getCastMap(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(DripCampaignStep::class)->orderBy('step_order');
    }
}
