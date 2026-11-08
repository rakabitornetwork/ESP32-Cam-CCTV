<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $organization = $request->user()->currentOrganization();

        return Inertia::render('Dashboard', [
            'stats' => [
                'devices' => $organization?->devices()->count() ?? 0,
                'recordings' => $organization?->recordings()->count() ?? 0,
                'captures' => $organization?->captures()->count() ?? 0,
                'quota' => [
                    'max_bytes' => $organization?->storageQuota?->max_bytes ?? 0,
                    'used_bytes' => $organization?->storageQuota?->used_bytes ?? 0,
                ],
                'plan' => $organization?->subscription?->plan?->name,
            ],
        ]);
    }
}
