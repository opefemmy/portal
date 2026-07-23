<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'amount',
        'is_active',
        'requires_payment',
        'payment_channel',
        'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_payment' => 'boolean',
        'amount' => 'decimal:2',
    ];

    public function externalPayments(): HasMany
    {
        return $this->hasMany(ExternalPayment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequiresPayment($query)
    {
        return $query->where('requires_payment', true);
    }
}
