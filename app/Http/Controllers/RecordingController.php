<?php

namespace App\Http\Controllers;

use App\Models\Recording;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecordingController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Recording::class);
        $organization = $request->user()->currentOrganization();
        $filters = $request->validate([
            'device_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $recordings = $organization->recordings()
            ->with('device:id,name')
            ->when($filters['device_id'] ?? null, fn ($query, $deviceId) => $query->where('device_id', $deviceId))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('started_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('started_at', '<=', $to))
            ->latest('started_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Recording $recording) => [
                'id' => $recording->id,
                'uuid' => $recording->uuid,
                'device_name' => $recording->device?->name,
                'started_at' => $recording->started_at?->toIso8601String(),
                'bytes' => $recording->bytes,
                'status' => $recording->status->value,
                'mime' => $recording->mime,
            ]);

        return Inertia::render('Recordings/Index', [
            'recordings' => $recordings,
            'devices' => $organization->devices()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'device_id' => $filters['device_id'] ?? '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
            ],
        ]);
    }

    public function show(Recording $recording): Response
    {
        $this->authorize('view', $recording);
        $recording->load('device:id,name');

        return Inertia::render('Recordings/Show', [
            'recording' => [
                'id' => $recording->id,
                'uuid' => $recording->uuid,
                'device_name' => $recording->device?->name,
                'started_at' => $recording->started_at?->toIso8601String(),
                'ended_at' => $recording->ended_at?->toIso8601String(),
                'bytes' => $recording->bytes,
                'status' => $recording->status->value,
                'mime' => $recording->mime,
            ],
        ]);
    }

    public function stream(Recording $recording): StreamedResponse
    {
        $this->authorize('view', $recording);
        abort_unless($recording->path, 404);

        return Storage::disk($recording->disk ?: 'cctv')->response(
            $recording->path,
            null,
            ['Content-Type' => $recording->mime ?: 'video/mp4'],
        );
    }

    public function download(Recording $recording): StreamedResponse
    {
        $this->authorize('view', $recording);
        abort_unless($recording->path, 404);

        return Storage::disk($recording->disk ?: 'cctv')->download(
            $recording->path,
            $recording->uuid.'.mp4',
        );
    }

    public function destroy(Recording $recording): RedirectResponse
    {
        $this->authorize('delete', $recording);
        $bytes = (int) $recording->bytes;
        $recording->deleteStoredFile();
        $recording->delete();
        $quota = $recording->organization->storageQuota;

        if ($quota !== null) {
            $quota->forceFill([
                'used_bytes' => max(0, $quota->used_bytes - $bytes),
            ])->save();
        }

        return redirect()->route('recordings.index');
    }
}
