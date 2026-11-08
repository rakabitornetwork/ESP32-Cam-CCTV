<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Plan;

class BillingService
{
    public function choosePlan(Organization $organization, Plan $plan): ?Invoice
    {
        if (! $plan->is_public) {
            abort(404);
        }

        if ($plan->price_idr === 0) {
            $this->activate($organization, $plan);

            return null;
        }

        return $organization->invoices()->create([
            'plan_id' => $plan->id,
            'number' => 'INV-'.now()->format('YmdHis').'-'.$organization->id,
            'amount_idr' => $plan->price_idr,
            'status' => InvoiceStatus::Pending,
            'gateway' => 'manual',
        ]);
    }

    public function markPaid(Invoice $invoice): void
    {
        if ($invoice->status === InvoiceStatus::Paid) {
            return;
        }

        if ($invoice->status !== InvoiceStatus::Pending || $invoice->plan === null) {
            abort(422, 'Faktur tidak dapat dibayar.');
        }

        $invoice->forceFill([
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
        ])->save();

        $this->activate($invoice->organization, $invoice->plan);
    }

    public function activate(Organization $organization, Plan $plan): void
    {
        $organization->subscription()->update([
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'canceled_at' => null,
        ]);

        $organization->storageQuota()->update([
            'max_bytes' => $plan->max_bytes,
        ]);

        $organization->forceFill([
            'retain_days' => $plan->retain_days,
        ])->save();
    }
}
