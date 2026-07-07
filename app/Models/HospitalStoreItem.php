<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HospitalStoreItem extends Model
{
    protected $fillable = [
        'name',
        'code',
        'category',
        'unit',
        'cost_price',
        'selling_price',
        'current_stock',
        'reorder_level',
        'description',
        'is_active',
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
}