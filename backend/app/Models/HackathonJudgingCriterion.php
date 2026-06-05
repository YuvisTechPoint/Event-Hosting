<?php

declare(strict_types=1);

namespace HiEvents\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HackathonJudgingCriterion extends BaseModel
{
    protected $table = 'hackathon_judging_criteria';

    protected function getCastMap(): array
    {
        return [
            'max_score' => 'integer',
            'weight' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function judgingRound(): BelongsTo
    {
        return $this->belongsTo(HackathonJudgingRound::class, 'judging_round_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(HackathonScore::class, 'criteria_id');
    }
}
