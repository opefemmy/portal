<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;

class HospitalPurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id', 'item_id', 'item_type', 'item_name', 'quantity', 'unit_cost',
        'total', 'batch_number', 'expiry_date'
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(HospitalPurchase::class, 'purchase_id');
    }
}