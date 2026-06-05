<?php

declare(strict_types=1);

namespace HiEvents\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunityFollow extends BaseModel
{
    protected $table = 'community_follows';

    public $timestamps = false;

    protected function getFillableFields(): array
    {
        return [
            'follower_user_id',
            'target_type',
            'target_id',
            'created_at',
        ];
    }

    protected function getTimestampsEnabled(): bool
    {
        return false;
    }

    public function follower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follower_user_id');
    }
}
