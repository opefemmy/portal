<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalStoreItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'code', 'category', 'unit', 'cost_price', 'selling_price',
        'current_stock', 'reorder_level', 'description', 'is_active'
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(HospitalStoreBatch::class, 'item_id');
    }

    public function activeBatches(): HasMany
    {
        return $this->hasMany(HospitalStoreBatch::class, 'item_id')->where('status', 'active');
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->reorder_level;
    }
}