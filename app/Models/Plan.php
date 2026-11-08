<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    public const int GIB = 1073741824;

    protected $fillable = [
        'slug',
        'name',
        'price_idr',
        'max_bytes',
        'max_devices',
        'retain_days',
        'max_captures_per_day',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'price_idr' => 'integer',
            'max_bytes' => 'integer',
            'max_devices' => 'integer',
            'retain_days' => 'integer',
            'max_captures_per_day' => 'integer',
            'is_public' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public static function ensureDefaults(): void
    {
        foreach (self::defaults() as $row) {
            self::query()->updateOrCreate(['slug' => $row['slug']], $row);
        }
    }

    public static function gratis(): self
    {
        $row = collect(self::defaults())->firstWhere('slug', 'gratis');

        return self::query()->firstOrCreate(['slug' => 'gratis'], $row);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function defaults(): array
    {
        return [
            [
                'slug' => 'gratis',
                'name' => 'Gratis',
                'price_idr' => 0,
                'max_bytes' => self::GIB,
                'max_devices' => 1,
                'retain_days' => 7,
                'max_captures_per_day' => 50,
                'is_public' => true,
            ],
            [
                'slug' => 'dasar',
                'name' => 'Dasar',
                'price_idr' => 49000,
                'max_bytes' => 20 * self::GIB,
                'max_devices' => 3,
                'retain_days' => 30,
                'max_captures_per_day' => 500,
                'is_public' => true,
            ],
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'price_idr' => 149000,
                'max_bytes' => 100 * self::GIB,
                'max_devices' => 10,
                'retain_days' => 90,
                'max_captures_per_day' => null,
                'is_public' => true,
            ],
        ];
    }
}
