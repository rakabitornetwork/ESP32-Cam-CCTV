<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'users' => User::query()->latest()->limit(50)->get(['id', 'name', 'email', 'is_admin', 'created_at']),
            'organizations' => Organization::query()
                ->with(['subscription.plan:id,name', 'storageQuota:id,organization_id,max_bytes,used_bytes'])
                ->withCount(['devices', 'users'])
                ->latest()
                ->limit(50)
                ->get(),
            'invoices' => Invoice::query()->with('organization:id,name')->latest()->limit(30)->get(),
        ]);
    }
}
