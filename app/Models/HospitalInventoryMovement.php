<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalInventoryMovement extends Model
{
    protected $fillable = [
        'drug_id',
        'batch_id',
        'user_id',
        'movement_type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'unit_cost',
        'reference',
        'notes',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
    ];

    public function drug(): BelongsTo
    {
        return $this->belongsTo(HospitalDrug::class, 'drug_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(HospitalDrugBatch::class, 'batch_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}