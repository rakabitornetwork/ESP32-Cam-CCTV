<?php

namespace App\Actions;

use App\Enums\RecordingStatus;
use App\Models\Recording;
use App\Models\StorageQuota;
use App\Services\SubscriptionGate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AssembleRecording
{
    public function __construct(private SubscriptionGate $gate) {}

    public function handle(Recording $recording): int
    {
        $disk = Storage::disk($recording->disk ?: 'cctv');
        $directory = $recording->chunkDirectory();
        $chunks = collect($disk->files($directory))
            ->filter(fn (string $path): bool => str_starts_with(basename($path), 'chunk-'))
            ->sort()
            ->values();

        if ($chunks->isEmpty()) {
            abort(422, 'Tidak ada potongan unggahan.');
        }

        $temporary = tempnam(sys_get_temp_dir(), 'cctv');
        $output = fopen($temporary, 'wb');

        foreach ($chunks as $chunk) {
            $input = $disk->readStream($chunk);

            if (is_resource($input)) {
                stream_copy_to_stream($input, $output);
                fclose($input);
            }
        }

        fclose($output);
        $bytes = (int) filesize($temporary);

        $organization = $recording->organization()->with(['subscription.plan', 'storageQuota'])->first();

        try {
            $this->gate->assertCanStore($organization, $bytes);
        } catch (HttpException $exception) {
            @unlink($temporary);
            $recording->forceFill(['status' => RecordingStatus::Failed])->save();

            throw $exception;
        }

        $final = $directory.'/final.mp4';
        $stream = fopen($temporary, 'rb');
        $disk->put($final, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        @unlink($temporary);

        foreach ($chunks as $chunk) {
            $disk->delete($chunk);
        }

        DB::transaction(function () use ($recording, $bytes, $final, $organization): void {
            $recording->forceFill([
                'path' => $final,
                'bytes' => $bytes,
                'status' => RecordingStatus::Ready,
                'ended_at' => now(),
            ])->save();

            $quota = StorageQuota::query()
                ->where('organization_id', $organization->id)
                ->lockForUpdate()
                ->first();

            $quota?->increment('used_bytes', $bytes);
        });

        return $bytes;
    }
}
