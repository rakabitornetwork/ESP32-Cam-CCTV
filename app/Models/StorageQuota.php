<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorageQuota extends Model
{
    protected $fillable = [
        'organization_id',
        'max_bytes',
        'used_bytes',
    ];

    protected function casts(): array
    {
        return [
            'max_bytes' => 'integer',
            'used_bytes' => 'integer',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function remainingBytes(): int
    {
        return max(0, $this->max_bytes - $this->used_bytes);
    }
}
