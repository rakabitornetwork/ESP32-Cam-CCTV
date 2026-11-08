<?php

namespace App\Services;

use App\Models\Capture;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SubscriptionGate
{
    public function captureAllowed(Organization $organization): bool
    {
        try {
            $this->assertCanAcceptCapture($organization, 1);
        } catch (HttpException) {
            return false;
        }

        return true;
    }

    public function assertCanStore(Organization $organization, int $incomingBytes): void
    {
        $subscription = $this->subscription($organization);

        if (! $subscription->allowsUploads()) {
            abort(402, 'Langganan tidak aktif.');
        }

        $quota = $organization->relationLoaded('storageQuota')
            ? $organization->storageQuota
            : $organization->storageQuota()->first();

        if ($quota !== null && ($quota->used_bytes + $incomingBytes) > $quota->max_bytes) {
            abort(402, 'Kuota penyimpanan penuh.');
        }
    }

    public function assertCanAcceptCapture(Organization $organization, int $incomingBytes): void
    {
        $this->assertCanStore($organization, $incomingBytes);

        $cap = $this->subscription($organization)->plan?->max_captures_per_day;

        if ($cap === null) {
            return;
        }

        $usedToday = Capture::query()
            ->where('organization_id', $organization->id)
            ->whereDate('created_at', today())
            ->count();

        if ($usedToday >= $cap) {
            abort(429, 'Batas tangkapan harian tercapai.');
        }
    }

    public function assertCanAddDevice(Organization $organization): void
    {
        $subscription = $this->subscription($organization);

        if (! $subscription->allowsUploads()) {
            throw ValidationException::withMessages([
                'name' => 'Langganan tidak aktif.',
            ]);
        }

        $maxDevices = $subscription->plan?->max_devices ?? 0;

        if ($organization->devices()->count() >= $maxDevices) {
            throw ValidationException::withMessages([
                'name' => 'Jumlah perangkat sudah mencapai batas paket.',
            ]);
        }
    }

    public function capturesRemainingToday(Organization $organization): ?int
    {
        $cap = $this->subscription($organization)->plan?->max_captures_per_day;

        if ($cap === null) {
            return null;
        }

        $usedToday = Capture::query()
            ->where('organization_id', $organization->id)
            ->whereDate('created_at', today())
            ->count();

        return max(0, $cap - $usedToday);
    }

    private function subscription(Organization $organization): Subscription
    {
        $subscription = $organization->relationLoaded('subscription')
            ? $organization->subscription
            : $organization->subscription()->with('plan')->first();

        if ($subscription === null) {
            abort(402, 'Langganan tidak aktif.');
        }

        if (! $subscription->relationLoaded('plan')) {
            $subscription->load('plan');
        }

        return $subscription;
    }
}
