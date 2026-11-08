<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesDevice;
use App\Http\Controllers\Controller;
use App\Services\SubscriptionGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceMeController extends Controller
{
    use ResolvesDevice;

    public function __invoke(Request $request, SubscriptionGate $gate): JsonResponse
    {
        $device = $this->device($request);
        $organization = $device->organization()->with(['subscription.plan', 'storageQuota'])->first();
        $quota = $organization->storageQuota;

        return response()->json([
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'location' => $device->location,
                'latitude' => $device->latitude,
                'longitude' => $device->longitude,
            ],
            'capture_allowed' => $gate->captureAllowed($organization),
            'image_quality' => $device->image_quality ?: 'standar',
            'image_settings' => $device->imageSettings(),
            'preview' => $device->previewActive(),
            'bytes_remaining' => $quota?->remainingBytes() ?? 0,
            'captures_remaining_today' => $gate->capturesRemainingToday($organization),
            'plan_status' => $organization->subscription?->status?->value,
            'quota' => [
                'max_bytes' => $quota?->max_bytes,
                'used_bytes' => $quota?->used_bytes,
            ],
        ]);
    }
}
