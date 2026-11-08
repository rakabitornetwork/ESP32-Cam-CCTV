<?php

namespace App\Models;

use App\Enums\RecordingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Recording extends Model
{
    protected $fillable = [
        'uuid',
        'organization_id',
        'device_id',
        'started_at',
        'ended_at',
        'bytes',
        'mime',
        'codec',
        'disk',
        'path',
        'status',
    ];

    protected $hidden = [
        'path',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'bytes' => 'integer',
            'status' => RecordingStatus::class,
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

    public function chunkDirectory(): string
    {
        return $this->organization_id.'/'.$this->device_id.'/'.$this->uuid;
    }

    public function deleteStoredFile(): void
    {
        $disk = Storage::disk($this->disk ?: 'cctv');

        if ($this->path) {
            $disk->delete($this->path);
        }

        $disk->deleteDirectory($this->chunkDirectory());
    }
}
