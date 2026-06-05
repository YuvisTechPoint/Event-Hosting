<?php

declare(strict_types=1);

namespace HiEvents\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HackathonProject extends BaseModel
{
    use SoftDeletes;

    protected function getCastMap(): array
    {
        return [
            'tech_stack' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(HackathonTeam::class, 'team_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(HackathonSubmission::class, 'project_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(HackathonScore::class, 'project_id');
    }
}
