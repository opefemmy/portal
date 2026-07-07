<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalDrugBatch extends Model
{
    protected $fillable = [
        'drug_id',
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

    public function drug(): BelongsTo
    {
        return $this->belongsTo(HospitalDrug::class, 'drug_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(HospitalSupplier::class, 'supplier_id');
    }
}