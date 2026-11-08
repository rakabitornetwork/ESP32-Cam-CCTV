<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $organization = $user?->currentOrganization();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                ] : null,
            ],
            'organization' => $organization ? [
                'id' => $organization->id,
                'name' => $organization->name,
                'retain_days' => $organization->retain_days,
                'role' => $organization->pivot->role,
                'plan' => $organization->subscription?->plan?->only([
                    'name', 'slug', 'max_bytes', 'max_devices', 'retain_days', 'max_captures_per_day',
                ]),
                'quota' => $organization->storageQuota?->only(['max_bytes', 'used_bytes']),
            ] : null,
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'update_result' => fn () => $request->session()->get('update_result'),
            ],
        ];
    }
}
