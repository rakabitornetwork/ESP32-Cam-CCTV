<?php

namespace App\Http\Controllers;

use App\Models\Capture;
use App\Models\Device;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $organization = $request->user()->currentOrganization();
        $photos = $organization?->captures()->where('trigger', 'pir');
        $startOfToday = Carbon::now('Asia/Jakarta')->startOfDay()->utc();

        return Inertia::render('Dashboard', [
            'summary' => [
                'cameras' => $organization?->devices()->count() ?? 0,
                'photos_today' => $photos ? (clone $photos)->where('captured_at', '>=', $startOfToday)->count() : 0,
                'photos' => $photos ? (clone $photos)->count() : 0,
                'storage_bytes' => $photos ? (int) (clone $photos)->sum('bytes') : 0,
            ],
            'cameras' => $organization
                ? $organization->devices()
                    ->withCount(['captures as photos_count' => fn ($query) => $query->where('trigger', 'pir')])
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Device $device) => [
                        'id' => $device->id,
                        'name' => $device->name,
                        'location' => $device->location,
                        'last_seen_at' => $device->last_seen_at?->toIso8601String(),
                        'photos' => $device->photos_count,
                    ])
                : [],
            'recent' => $organization
                ? $photos->with('device:id,name')->latest('captured_at')->limit(6)->get()
                    ->map(fn (Capture $capture) => [
                        'id' => $capture->id,
                        'device_name' => $capture->device?->name,
                        'captured_at' => $capture->captured_at?->toIso8601String(),
                        'rotation' => (int) $capture->rotation,
                    ])
                : [],
        ]);
    }
}
