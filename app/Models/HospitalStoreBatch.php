<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalStoreBatch extends Model
{
    protected $fillable = [
        'item_id',
        'batch_number',
        'quantity',
        'remaining_quantity',
        'unit_cost',
        'manufacture_date',
        'expiry_date',
        'received_date',
        'supplier_id',
        'status',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'received_date' => 'date',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(HospitalStoreItem::class, 'item_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(HospitalSupplier::class, 'supplier_id');
    }
}