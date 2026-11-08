<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesDevice;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePreviewRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class PreviewStoreController extends Controller
{
    use ResolvesDevice;

    public function __invoke(StorePreviewRequest $request): Response
    {
        $device = $this->device($request);
        if (! $device->previewActive()) {
            abort(409, 'Pratinjau tidak diminta.');
        }

        $stored = Storage::disk(config('cctv.disk', 'cctv'))->put(
            $device->previewPath(),
            $request->previewFile()->getContent(),
        );

        abort_unless($stored, 500, 'Gagal menyimpan pratinjau.');

        return response()->noContent();
    }
}
