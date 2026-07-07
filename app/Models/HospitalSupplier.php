<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HospitalSupplier extends Model
{
    protected $fillable = [
        'name',
        'code',
        'contact_person',
        'phone',
        'email',
        'address',
        'bank_name',
        'account_number',
        'account_name',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function drugBatches(): HasMany
    {
        return $this->hasMany(HospitalDrugBatch::class, 'supplier_id');
    }
}