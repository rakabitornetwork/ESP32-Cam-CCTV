<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RecordingStatus;
use App\Http\Controllers\Api\V1\Concerns\ResolvesDevice;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ChunkRecordingRequest;
use App\Models\Recording;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class RecordingChunkController extends Controller
{
    use ResolvesDevice;

    public function __invoke(ChunkRecordingRequest $request, Recording $recording): JsonResponse
    {
        $device = $this->device($request);
        abort_unless($recording->device_id === $device->id, 404);
        abort_unless($recording->status === RecordingStatus::Uploading, 409);

        $name = sprintf('chunk-%05d', $request->integer('index'));
        Storage::disk($recording->disk ?: 'cctv')->putFileAs(
            $recording->chunkDirectory(),
            $request->file('chunk'),
            $name,
        );

        return response()->json([
            'index' => $request->integer('index'),
        ]);
    }
}
