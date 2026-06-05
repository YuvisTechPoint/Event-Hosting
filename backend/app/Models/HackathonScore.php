<?php

declare(strict_types=1);

namespace HiEvents\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HackathonScore extends BaseModel
{
    protected function getCastMap(): array
    {
        return ['score' => 'float'];
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(HackathonJudgingCriterion::class, 'criteria_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(HackathonProject::class, 'project_id');
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(User::class, 'judge_user_id');
    }
}
