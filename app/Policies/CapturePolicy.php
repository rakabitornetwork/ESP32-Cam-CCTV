<?php

namespace App\Policies;

use App\Models\Capture;
use App\Models\User;

class CapturePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentOrganization() !== null;
    }

    public function view(User $user, Capture $capture): bool
    {
        return $user->belongsToOrganization($capture->organization_id);
    }

    public function rotate(User $user, Capture $capture): bool
    {
        return $this->view($user, $capture);
    }

    public function delete(User $user, Capture $capture): bool
    {
        return $user->canManageOrganization($capture->organization_id);
    }

    public function clear(User $user): bool
    {
        $organization = $user->currentOrganization();

        return $organization !== null && $user->canManageOrganization($organization->id);
    }
}
