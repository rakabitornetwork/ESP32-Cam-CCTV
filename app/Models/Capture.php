<?php

namespace App\Models;

use App\Enums\CaptureTrigger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Capture extends Model
{
    protected $fillable = [
        'organization_id',
        'device_id',
        'trigger',
        'bytes',
        'disk',
        'path',
        'captured_at',
        'rotation',
        'latitude',
        'longitude',
    ];

    protected $hidden = [
        'path',
    ];

    protected function casts(): array
    {
        return [
            'trigger' => CaptureTrigger::class,
            'bytes' => 'integer',
            'captured_at' => 'datetime',
            'rotation' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function deleteStoredFile(): void
    {
        if ($this->path) {
            Storage::disk($this->disk ?: 'cctv')->delete($this->path);
        }
    }
}
