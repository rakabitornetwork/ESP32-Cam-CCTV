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

        $bytes = $request->previewFile()->getContent();
        $complete = str_starts_with($bytes, "\xFF\xD8") && str_ends_with($bytes, "\xFF\xD9");
        abort_unless($complete, 422, 'Gambar tidak utuh.');

        $disk = Storage::disk(config('cctv.disk', 'cctv'));
        $final = $device->previewPath();
        $temporary = $final.'.tmp';
        abort_unless($disk->put($temporary, $bytes), 500, 'Gagal menyimpan pratinjau.');
        rename($disk->path($temporary), $disk->path($final));

        return response()->noContent();
    }
}
