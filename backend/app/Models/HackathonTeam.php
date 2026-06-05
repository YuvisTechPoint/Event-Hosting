<?php

declare(strict_types=1);

namespace HiEvents\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class HackathonTeam extends BaseModel
{
    use SoftDeletes;

    protected function getCastMap(): array
    {
        return ['max_members' => 'integer'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(HackathonTeamMember::class, 'team_id');
    }

    public function project(): HasOne
    {
        return $this->hasOne(HackathonProject::class, 'team_id');
    }
}
