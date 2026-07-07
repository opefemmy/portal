<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalStoreBatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'item_id', 'batch_number', 'quantity', 'remaining_quantity', 'unit_cost',
        'manufacture_date', 'expiry_date', 'received_date', 'supplier_id', 'status'
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'received_date' => 'date',
        'unit_cost' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(HospitalStoreItem::class, 'item_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(HospitalSupplier::class, 'supplier_id');
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }
}