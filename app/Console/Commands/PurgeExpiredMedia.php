<?php

namespace App\Console\Commands;

use App\Models\Organization;
use Illuminate\Console\Command;

class PurgeExpiredMedia extends Command
{
    protected $signature = 'cctv:purge';

    protected $description = 'Hapus rekaman dan tangkapan yang melewati masa simpan organisasi';

    public function handle(): int
    {
        Organization::query()->with('storageQuota')->each(function (Organization $organization): void {
            $cutoff = now()->subDays($organization->retain_days);
            $freed = 0;

            $organization->recordings()
                ->where('started_at', '<', $cutoff)
                ->each(function ($recording) use (&$freed): void {
                    $freed += (int) $recording->bytes;
                    $recording->deleteStoredFile();
                    $recording->delete();
                });

            $organization->captures()
                ->where('captured_at', '<', $cutoff)
                ->each(function ($capture) use (&$freed): void {
                    $freed += (int) $capture->bytes;
                    $capture->deleteStoredFile();
                    $capture->delete();
                });

            if ($freed > 0 && $organization->storageQuota !== null) {
                $organization->storageQuota->forceFill([
                    'used_bytes' => max(0, $organization->storageQuota->used_bytes - $freed),
                ])->save();
            }
        });

        return self::SUCCESS;
    }
}
