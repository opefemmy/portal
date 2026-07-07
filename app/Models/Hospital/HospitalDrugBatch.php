<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalDrugBatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'drug_id', 'batch_number', 'quantity', 'remaining_quantity', 'unit_cost',
        'manufacture_date', 'expiry_date', 'received_date', 'supplier_id', 'status'
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'received_date' => 'date',
        'unit_cost' => 'decimal:2',
    ];

    public function drug(): BelongsTo
    {
        return $this->belongsTo(HospitalDrug::class, 'drug_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(HospitalSupplier::class, 'supplier_id');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date->isPast();
    }

    public function isExpiringSoon(): bool
    {
        return $this->expiry_date->diffInDays(now()) <= 30;
    }

    public function isLow(): bool
    {
        return $this->remaining_quantity <= 10;
    }
}