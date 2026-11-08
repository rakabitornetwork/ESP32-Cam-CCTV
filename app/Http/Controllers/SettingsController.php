<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateOrganizationSettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
