<?php

declare(strict_types=1);

namespace HiEvents\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HackathonSubmission extends BaseModel
{
    protected function getCastMap(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(HackathonProject::class, 'project_id');
    }

    public function judgingRound(): BelongsTo
    {
        return $this->belongsTo(HackathonJudgingRound::class, 'judging_round_id');
    }
}
