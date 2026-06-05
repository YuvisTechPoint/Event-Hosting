<?php

declare(strict_types=1);

namespace HiEvents\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HackathonTeamMember extends BaseModel
{
    public function team(): BelongsTo
    {
        return $this->belongsTo(HackathonTeam::class, 'team_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
