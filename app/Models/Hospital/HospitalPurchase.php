<?php

namespace App\Models\Hospital;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalPurchase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'purchase_number', 'supplier_id', 'requested_by', 'approved_by', 'purchase_date',
        'expected_delivery', 'actual_delivery', 'subtotal', 'tax', 'total', 'status', 'notes'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'expected_delivery' => 'date',
        'actual_delivery' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(HospitalSupplier::class, 'supplier_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(HospitalPurchaseItem::class, 'purchase_id');
    }
}