<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ApplicationUpdater;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UpdateController extends Controller
{
    public function show(ApplicationUpdater $updater): Response
    {
        return Inertia::render('Admin/Update', [
            'status' => $updater->status(),
        ]);
    }

    public function check(ApplicationUpdater $updater): RedirectResponse
    {
        return back()->with('update_result', $updater->check());
    }

    public function apply(ApplicationUpdater $updater): RedirectResponse
    {
        return back()->with('update_result', $updater->apply());
    }
}
