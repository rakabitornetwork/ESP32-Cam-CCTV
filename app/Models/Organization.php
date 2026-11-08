<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organization extends Model
{
    protected $fillable = [
        'name',
        'retain_days',
    ];

    protected function casts(): array
    {
        return [
            'retain_days' => 'integer',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function recordings(): HasMany
    {
        return $this->hasMany(Recording::class);
    }

    public function captures(): HasMany
    {
        return $this->hasMany(Capture::class);
    }

    public function storageQuota(): HasOne
    {
        return $this->hasOne(StorageQuota::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public static function provisionFor(User $user): self
    {
        Plan::ensureDefaults();
        $plan = Plan::query()->where('slug', 'gratis')->firstOrFail();

        $organization = self::query()->create([
            'name' => $user->name,
            'retain_days' => $plan->retain_days,
        ]);

        $organization->users()->attach($user->id, [
            'role' => OrganizationRole::Owner->value,
        ]);

        $organization->storageQuota()->create([
            'max_bytes' => $plan->max_bytes,
            'used_bytes' => 0,
        ]);

        $organization->subscription()->create([
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        return $organization;
    }
}
