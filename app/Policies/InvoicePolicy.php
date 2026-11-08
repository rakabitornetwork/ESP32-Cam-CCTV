<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function pay(User $user, Invoice $invoice): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $user->canManageOrganization($invoice->organization_id);
    }
}
