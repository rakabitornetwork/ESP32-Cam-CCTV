<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlanChoiceRequest;
use App\Models\Invoice;
use App\Models\Plan;
use App\Services\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        $organization = $request->user()->currentOrganization();
        abort_unless($organization, 403);

        return Inertia::render('Billing/Index', [
            'plans' => Plan::query()->where('is_public', true)->orderBy('price_idr')->get([
                'id', 'slug', 'name', 'price_idr', 'max_bytes', 'max_devices', 'retain_days', 'max_captures_per_day',
            ]),
            'invoices' => $organization->invoices()->latest()->limit(20)->get([
                'id', 'number', 'amount_idr', 'status', 'gateway', 'paid_at', 'created_at',
            ]),
            'canManage' => $request->user()->canManageOrganization($organization->id),
        ]);
    }

    public function store(StorePlanChoiceRequest $request, BillingService $billing): RedirectResponse
    {
        $organization = $request->user()->currentOrganization();
        $plan = Plan::query()->findOrFail($request->integer('plan_id'));
        $invoice = $billing->choosePlan($organization, $plan);

        if ($invoice === null) {
            return back()->with('status', 'Paket Gratis aktif.');
        }

        return back()->with('status', 'Faktur '.$invoice->number.' menunggu pembayaran.');
    }

    public function pay(Request $request, Invoice $invoice, BillingService $billing): RedirectResponse
    {
        $this->authorize('pay', $invoice);
        $billing->markPaid($invoice);

        return back()->with('status', 'Pembayaran dicatat.');
    }
}
