<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class FinancePurchaseOrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['po_id', 'item_name', 'quantity', 'unit_cost', 'total'];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(FinancePurchaseOrder::class, 'po_id');
    }
}