<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;

class DevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentOrganization() !== null;
    }

    public function view(User $user, Device $device): bool
    {
        return $user->belongsToOrganization($device->organization_id);
    }

    public function create(User $user): bool
    {
        $organization = $user->currentOrganization();

        return $organization !== null && $user->canManageOrganization($organization->id);
    }

    public function update(User $user, Device $device): bool
    {
        return $user->canManageOrganization($device->organization_id);
    }

    public function delete(User $user, Device $device): bool
    {
        return $this->update($user, $device);
    }
}
