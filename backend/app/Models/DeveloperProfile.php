<?php

declare(strict_types=1);

namespace HiEvents\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeveloperProfile extends BaseModel
{
    use SoftDeletes;

    protected $table = 'developer_profiles';

    protected function getCastMap(): array
    {
        return [
            'is_public' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected function getFillableFields(): array
    {
        return [
            'user_id',
            'username',
            'headline',
            'bio',
            'github_username',
            'website_url',
            'location',
            'is_public',
            'metadata',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
