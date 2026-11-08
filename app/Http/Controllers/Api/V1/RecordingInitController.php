<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RecordingStatus;
use App\Http\Controllers\Api\V1\Concerns\ResolvesDevice;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InitRecordingRequest;
use App\Services\SubscriptionGate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class RecordingInitController extends Controller
{
    use ResolvesDevice;

    public function __invoke(InitRecordingRequest $request, SubscriptionGate $gate): JsonResponse
    {
        $device = $this->device($request);
        $organization = $device->organization()->with(['subscription.plan', 'storageQuota'])->first();
        $gate->assertCanStore($organization, (int) $request->input('bytes_total', 1));

        $recording = $device->recordings()->create([
            'uuid' => (string) Str::uuid(),
            'organization_id' => $organization->id,
            'started_at' => $request->date('started_at'),
            'mime' => $request->input('mime', 'video/mp4'),
            'disk' => config('cctv.disk', 'cctv'),
            'status' => RecordingStatus::Uploading,
            'bytes' => 0,
        ]);

        return response()->json([
            'recording_id' => $recording->id,
            'uuid' => $recording->uuid,
            'next_chunk_index' => 0,
        ], 201);
    }
}
