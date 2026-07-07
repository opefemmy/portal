<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalSupplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'code', 'contact_person', 'phone', 'email', 'address',
        'bank_name', 'account_number', 'account_name', 'notes', 'is_active'
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function drugBatches(): HasMany
    {
        return $this->hasMany(HospitalDrugBatch::class, 'supplier_id');
    }

    public function storeBatches(): HasMany
    {
        return $this->hasMany(HospitalStoreBatch::class, 'supplier_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(HospitalPurchase::class, 'supplier_id');
    }
}