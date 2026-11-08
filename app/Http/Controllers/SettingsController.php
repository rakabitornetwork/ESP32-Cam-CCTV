<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCameraImageRequest;
use App\Http\Requests\UpdateOrganizationSettingsRequest;
use App\Models\Device;
use App\Support\CameraImageSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $organization = $request->user()->currentOrganization();
        abort_unless($organization, 403);

        return Inertia::render('Settings/Edit', [
            'retainDays' => $organization->retain_days,
            'maxRetainDays' => $organization->subscription?->plan?->retain_days ?? $organization->retain_days,
            'canManage' => $request->user()->canManageOrganization($organization->id),
            'cameras' => $organization->devices()->orderBy('name')->get()->map(fn (Device $device) => [
                'id' => $device->id,
                'name' => $device->name,
                'image_quality' => $device->image_quality ?: 'standar',
                'image_settings' => $device->imageSettings(),
            ])->values(),
        ]);
    }

    public function updateCamera(UpdateCameraImageRequest $request, Device $device): RedirectResponse
    {
        $device->update([
            'image_quality' => $request->validated('image_quality'),
            'image_settings' => CameraImageSettings::normalize($request->validated('image_settings')),
        ]);

        return back();
    }

    public function pulsePreview(Request $request, Device $device): HttpResponse
    {
        $this->authorize('view', $device);

        $device->forceFill([
            'preview_until' => now()->addSeconds(25),
        ])->save();

        return response()->noContent();
    }

    public function showPreview(Request $request, Device $device): HttpResponse
    {
        $this->authorize('view', $device);

        $disk = Storage::disk(config('cctv.disk', 'cctv'));
        $path = $device->previewPath();
        abort_unless($disk->exists($path), 404);

        return response($disk->get($path), 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function update(UpdateOrganizationSettingsRequest $request): RedirectResponse
    {
        $request->user()->currentOrganization()->update([
            'retain_days' => $request->integer('retain_days'),
        ]);

        return back()->with('status', 'Pengaturan disimpan.');
    }
}
