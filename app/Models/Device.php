<?php

namespace App\Models;

use App\Support\CameraImageSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Device extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'location',
        'latitude',
        'longitude',
        'token_hash',
        'token_prefix',
        'last_seen_at',
        'pir_armed',
        'rssi',
        'firmware',
        'image_quality',
        'image_settings',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'last_seen_at' => 'datetime',
            'pir_armed' => 'boolean',
            'rssi' => 'integer',
            'image_settings' => 'array',
            'preview_until' => 'datetime',
        ];
    }

    public function previewActive(): bool
    {
        return $this->preview_until !== null && $this->preview_until->isFuture();
    }

    public function previewPath(): string
    {
        return 'preview/'.$this->organization_id.'/'.$this->id.'.jpg';
    }

    /**
     * @return array<string, int|bool>
     */
    public function imageSettings(): array
    {
        return CameraImageSettings::normalize($this->image_settings);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(Recording::class);
    }

    public function captures(): HasMany
    {
        return $this->hasMany(Capture::class);
    }

    /**
     * @return array{device: self, plain_token: string}
     */
    public static function provision(Organization $organization, string $name, ?string $location = null, ?float $latitude = null, ?float $longitude = null): array
    {
        $plainToken = 'cctv_'.Str::random(48);

        $device = $organization->devices()->create([
            'name' => $name,
            'location' => $location,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'token_hash' => hash('sha256', $plainToken),
            'token_prefix' => substr($plainToken, 0, 12),
        ]);

        return ['device' => $device, 'plain_token' => $plainToken];
    }

    public function rotateToken(): string
    {
        $plainToken = 'cctv_'.Str::random(48);

        $this->forceFill([
            'token_hash' => hash('sha256', $plainToken),
            'token_prefix' => substr($plainToken, 0, 12),
        ])->save();

        return $plainToken;
    }

    public function touchLastSeen(): void
    {
        $this->forceFill(['last_seen_at' => now()])->save();
    }
}
