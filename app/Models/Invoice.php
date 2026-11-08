<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'organization_id',
        'plan_id',
        'number',
        'amount_idr',
        'status',
        'gateway',
        'gateway_ref',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_idr' => 'integer',
            'status' => InvoiceStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
