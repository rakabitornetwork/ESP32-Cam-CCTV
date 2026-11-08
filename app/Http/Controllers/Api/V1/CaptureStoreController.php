<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CaptureTrigger;
use App\Http\Controllers\Api\V1\Concerns\ResolvesDevice;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCaptureRequest;
use App\Services\SubscriptionGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CaptureStoreController extends Controller
{
    use ResolvesDevice;

    public function __invoke(StoreCaptureRequest $request, SubscriptionGate $gate): JsonResponse
    {
        $device = $this->device($request);
        $file = $request->captureFile();
        $organization = $device->organization()->with(['subscription.plan', 'storageQuota'])->first();
        $gate->assertCanAcceptCapture($organization, (int) $file->getSize());

        $capturedAt = $request->date('captured_at') ?? now();
        $directory = $organization->id.'/'.$device->id.'/captures/'.$capturedAt->format('Y/m/d');
        $filename = $capturedAt->format('His').'_'.Str::random(6).'.jpg';
        $disk = config('cctv.disk', 'cctv');
        $path = $file->storeAs($directory, $filename, $disk);

        if ($path === false) {
            abort(500, 'Gagal menyimpan tangkapan.');
        }

        $capture = $organization->captures()->create([
            'device_id' => $device->id,
            'trigger' => $request->input('trigger', CaptureTrigger::Pir->value),
            'bytes' => (int) $file->getSize(),
            'disk' => $disk,
            'path' => $path,
            'captured_at' => $capturedAt,
            'latitude' => $request->filled('latitude') ? $request->float('latitude') : null,
            'longitude' => $request->filled('longitude') ? $request->float('longitude') : null,
        ]);

        $organization->storageQuota?->increment('used_bytes', $capture->bytes);

        return response()->json([
            'id' => $capture->id,
            'captured_at' => $capture->captured_at->toIso8601String(),
        ], 201);
    }
}
