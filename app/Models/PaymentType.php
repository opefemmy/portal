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
        'purpose', // NEW: to categorize payment types
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_payment' => 'boolean',
        'amount' => 'decimal:2',
    ];

    // Purpose constants
    const PURPOSE_APPLICATION = 'application';
    const PURPOSE_SCHOOL_FEE = 'school_fee';
    const PURPOSE_ACCEPTANCE = 'acceptance';
    const PURPOSE_HOSTEL = 'hostel';
    const PURPOSE_REGISTRATION = 'registration';
    const PURPOSE_LIBRARY = 'library';
    const PURPOSE_OTHER = 'other';

    public static function getPurposes(): array
    {
        return [
            self::PURPOSE_APPLICATION => 'Application',
            self::PURPOSE_SCHOOL_FEE => 'School Fee',
            self::PURPOSE_ACCEPTANCE => 'Acceptance',
            self::PURPOSE_HOSTEL => 'Hostel',
            self::PURPOSE_REGISTRATION => 'Registration',
            self::PURPOSE_LIBRARY => 'Library',
            self::PURPOSE_OTHER => 'Other',
        ];
    }

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

    public function scopeForPurpose($query, string $purpose)
    {
        return $query->where('purpose', $purpose);
    }
}
