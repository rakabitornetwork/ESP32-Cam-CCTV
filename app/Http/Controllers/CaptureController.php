<?php

namespace App\Http\Controllers;

use App\Models\Capture;
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

        $captures = $organization->captures()
            ->with('device:id,name')
            ->where('trigger', 'pir')
            ->latest('captured_at')
            ->paginate(24)
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

    public function destroy(Capture $capture): RedirectResponse
    {
        $this->authorize('delete', $capture);
        $bytes = (int) $capture->bytes;
        $organization = $capture->organization;
        $capture->deleteStoredFile();
        $capture->delete();
        $quota = $organization->storageQuota;

        if ($quota !== null) {
            $quota->forceFill([
                'used_bytes' => max(0, $quota->used_bytes - $bytes),
            ])->save();
        }

        return redirect()->route('captures.index');
    }
}
