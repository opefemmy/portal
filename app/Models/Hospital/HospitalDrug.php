<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalDrug extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'name', 'generic_name', 'code', 'form', 'strength', 'unit',
        'cost_price', 'selling_price', 'reorder_level', 'current_stock', 'storage_location',
        'side_effects', 'contraindications', 'instructions', 'requires_prescription', 'is_active'
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

    public function prescriptionItems(): HasMany
    {
        return $this->hasMany(HospitalPrescriptionItem::class, 'drug_id');
    }

    public function activeBatches(): HasMany
    {
        return $this->hasMany(HospitalDrugBatch::class, 'drug_id')->where('status', 'active');
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->reorder_level;
    }

    public function isOutOfStock(): bool
    {
        return $this->current_stock <= 0;
    }
}