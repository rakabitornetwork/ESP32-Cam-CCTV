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

    public function delete(User $user, Capture $capture): bool
    {
        return $user->canManageOrganization($capture->organization_id);
    }
}
