<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceVendor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'code', 'contact_person', 'phone', 'email', 'address',
        'bank_name', 'account_number', 'account_name', 'tax_id', 'is_active', 'notes'
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(FinancePurchaseOrder::class, 'vendor_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FinanceVendorPayment::class, 'vendor_id');
    }
}