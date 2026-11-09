<?php

namespace App\Http\Controllers;

use App\Models\Capture;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CaptureController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Capture::class);
        $organization = $request->user()->currentOrganization();

        $cameras = $organization->devices()->orderBy('name')->get(['id', 'name']);
        $cameraId = $request->integer('kamera') ?: null;
        if ($cameraId !== null && ! $cameras->contains('id', $cameraId)) {
            $cameraId = null;
        }

        $captures = $organization->captures()
            ->with('device:id,name')
            ->where('trigger', 'pir')
            ->when($cameraId, fn ($query) => $query->where('device_id', $cameraId))
            ->latest('captured_at')
            ->paginate(24)
            ->withQueryString()
            ->through(fn (Capture $capture) => [
                'id' => $capture->id,
                'device_name' => $capture->device?->name,
                'trigger' => $capture->trigger->value,
                'bytes' => $capture->bytes,
                'rotation' => (int) $capture->rotation,
                'captured_at' => $capture->captured_at?->toIso8601String(),
            ]);

        return Inertia::render('Captures/Index', [
            'captures' => $captures,
            'cameras' => $cameras->map(fn ($device) => [
                'id' => $device->id,
                'name' => $device->name,
            ])->values(),
            'camera' => $cameraId,
            'canDelete' => $request->user()->canManageOrganization($organization->id),
        ]);
    }

    public function stream(Capture $capture): StreamedResponse
    {
        $this->authorize('view', $capture);
        abort_unless($capture->path, 404);

        return Storage::disk($capture->disk ?: 'cctv')->response(
            $capture->path,
            null,
            ['Content-Type' => 'image/jpeg'],
        );
    }

    public function rotate(Request $request, Capture $capture): RedirectResponse
    {
        $this->authorize('rotate', $capture);

        $direction = $request->validate([
            'direction' => ['required', 'string', Rule::in(['left', 'right'])],
        ])['direction'];

        $turns = $direction === 'right' ? 90 : 270;
        $capture->update([
            'rotation' => ((int) $capture->rotation + $turns) % 360,
        ]);

        return back();
    }

    public function destroy(Request $request, Capture $capture): RedirectResponse
    {
        $this->authorize('delete', $capture);
        $bytes = (int) $capture->bytes;
        $organization = $capture->organization;
        $capture->deleteStoredFile();
        $capture->delete();
        $this->releaseQuota($organization, $bytes);

        return redirect()->route('captures.index', $this->cameraQuery($request));
    }

    public function clear(Request $request): RedirectResponse
    {
        $this->authorize('clear', Capture::class);
        $organization = $request->user()->currentOrganization();
        abort_if($organization === null, 403);
        $cameraId = $request->integer('kamera') ?: null;

        if ($cameraId !== null && ! $organization->devices()->whereKey($cameraId)->exists()) {
            $cameraId = null;
        }

        $freed = 0;
        $organization->captures()
            ->where('trigger', 'pir')
            ->when($cameraId, fn ($query) => $query->where('device_id', $cameraId))
            ->chunkById(100, function ($captures) use (&$freed): void {
                foreach ($captures as $capture) {
                    $freed += (int) $capture->bytes;
                    $capture->deleteStoredFile();
                    $capture->delete();
                }
            });

        $this->releaseQuota($organization, $freed);

        return redirect()->route('captures.index', $cameraId ? ['kamera' => $cameraId] : []);
    }

    /**
     * @return array<string, int>
     */
    private function cameraQuery(Request $request): array
    {
        $cameraId = $request->integer('kamera') ?: null;

        return $cameraId ? ['kamera' => $cameraId] : [];
    }

    private function releaseQuota(Organization $organization, int $bytes): void
    {
        $quota = $organization->storageQuota()->first();

        if ($quota !== null && $bytes > 0) {
            $quota->forceFill([
                'used_bytes' => max(0, (int) $quota->used_bytes - $bytes),
            ])->save();
        }
    }
}
