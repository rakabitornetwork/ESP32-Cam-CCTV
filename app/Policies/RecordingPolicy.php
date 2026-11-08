<?php

namespace App\Policies;

use App\Models\Recording;
use App\Models\User;

class RecordingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->currentOrganization() !== null;
    }

    public function view(User $user, Recording $recording): bool
    {
        return $user->belongsToOrganization($recording->organization_id);
    }

    public function delete(User $user, Recording $recording): bool
    {
        return $user->canManageOrganization($recording->organization_id);
    }
}
