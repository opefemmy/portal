<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HospitalDrug extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'generic_name',
        'code',
        'form',
        'strength',
        'unit',
        'cost_price',
        'selling_price',
        'reorder_level',
        'current_stock',
        'storage_location',
        'side_effects',
        'contraindications',
        'instructions',
        'requires_prescription',
        'is_active',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'requires_prescription' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(HospitalDrugCategory::class, 'category_id');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(HospitalDrugBatch::class, 'drug_id');
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(HospitalInventoryMovement::class, 'drug_id');
    }
}