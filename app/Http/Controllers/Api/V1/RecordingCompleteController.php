<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\AssembleRecording;
use App\Enums\RecordingStatus;
use App\Http\Controllers\Api\V1\Concerns\ResolvesDevice;
use App\Http\Controllers\Controller;
use App\Models\Recording;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecordingCompleteController extends Controller
{
    use ResolvesDevice;

    public function __invoke(Request $request, Recording $recording, AssembleRecording $assemble): JsonResponse
    {
        $device = $this->device($request);
        abort_unless($recording->device_id === $device->id, 404);
        abort_unless($recording->status === RecordingStatus::Uploading, 409);

        $bytes = $assemble->handle($recording);

        return response()->json([
            'id' => $recording->id,
            'bytes' => $bytes,
            'status' => RecordingStatus::Ready->value,
        ]);
    }
}
