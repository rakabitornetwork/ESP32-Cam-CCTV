<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDeviceRequest;
use App\Http\Requests\UpdateDeviceRequest;
use App\Models\Capture;
use App\Models\Device;
use App\Models\Recording;
use App\Support\CameraImageSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DeviceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Device::class);
        $organization = $request->user()->currentOrganization();

        return Inertia::render('Devices/Index', [
            'devices' => $organization->devices()->latest()->get()->map(fn (Device $device) => $this->payload($device)),
            'plainToken' => $request->session()->pull('plain_token'),
            'tokenDevice' => $request->session()->pull('token_device'),
        ]);
    }

    public function map(Request $request): Response
    {
        $this->authorize('viewAny', Device::class);
        $organization = $request->user()->currentOrganization();

        return Inertia::render('Devices/Map', [
            'devices' => $organization->devices()->get()->map(fn (Device $device) => $this->payload($device)),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Device::class);

        return Inertia::render('Devices/Create');
    }

    public function store(StoreDeviceRequest $request): RedirectResponse
    {
        $organization = $request->user()->currentOrganization();

        $provisioned = Device::provision(
            $organization,
            $request->string('name')->toString(),
            $request->input('location'),
            $request->filled('latitude') ? $request->float('latitude') : null,
            $request->filled('longitude') ? $request->float('longitude') : null,
        );

        return redirect()
            ->route('devices.index')
            ->with('plain_token', $provisioned['plain_token'])
            ->with('token_device', $provisioned['device']->name);
    }

    public function show(Request $request, Device $device): Response
    {
        $this->authorize('view', $device);

        return Inertia::render('Devices/Show', [
            'device' => $this->payload($device),
            'plainToken' => $request->session()->pull('plain_token'),
        ]);
    }

    public function edit(Device $device): Response
    {
        $this->authorize('update', $device);

        return Inertia::render('Devices/Edit', [
            'device' => $this->payload($device),
        ]);
    }

    public function update(UpdateDeviceRequest $request, Device $device): RedirectResponse
    {
        $fields = $request->safe()->only(['name', 'location', 'latitude', 'longitude', 'image_quality']);
        if ($request->exists('image_settings')) {
            $fields['image_settings'] = CameraImageSettings::normalize($request->validated('image_settings'));
        }
        $device->update($fields);

        return redirect()->route('devices.index');
    }

    public function destroy(Device $device): RedirectResponse
    {
        $this->authorize('delete', $device);

        $bytes = (int) $device->captures()->sum('bytes') + (int) $device->recordings()->sum('bytes');
        $device->captures()->each(fn (Capture $capture) => $capture->deleteStoredFile());
        $device->recordings()->each(fn (Recording $recording) => $recording->deleteStoredFile());
        Storage::disk('cctv')->delete($device->previewPath());

        $organization = $device->organization;
        $device->delete();

        $quota = $organization->storageQuota;
        if ($quota !== null && $bytes > 0) {
            $quota->forceFill([
                'used_bytes' => max(0, $quota->used_bytes - $bytes),
            ])->save();
        }

        return redirect()->route('devices.index');
    }

    public function rotateToken(Device $device): RedirectResponse
    {
        $this->authorize('update', $device);

        return redirect()
            ->route('devices.show', $device)
            ->with('plain_token', $device->rotateToken());
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Device $device): array
    {
        return [
            'id' => $device->id,
            'name' => $device->name,
            'location' => $device->location,
            'latitude' => $device->latitude,
            'longitude' => $device->longitude,
            'token_prefix' => $device->token_prefix,
            'last_seen_at' => $device->last_seen_at?->toIso8601String(),
            'pir_armed' => $device->pir_armed,
            'rssi' => $device->rssi,
            'firmware' => $device->firmware,
            'image_quality' => $device->image_quality ?: 'standar',
            'image_settings' => $device->imageSettings(),
        ];
    }
}
