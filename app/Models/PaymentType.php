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
        'audience', // who this payment type is for: applicant, student, or both
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

    // Audience constants — who this payment type is meant for.
    // Default is 'both' so existing rows remain visible everywhere
    // until the admin re-classifies them.
    const AUDIENCE_APPLICANT = 'applicant';
    const AUDIENCE_STUDENT = 'student';
    const AUDIENCE_BOTH = 'both';

    public static function getAudiences(): array
    {
        return [
            self::AUDIENCE_APPLICANT => 'Applicant only',
            self::AUDIENCE_STUDENT => 'Student only',
            self::AUDIENCE_BOTH => 'Both applicant and student',
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

    /**
     * Restrict to rows visible to the given audience.
     * A row with audience='both' is visible to every audience;
     * 'applicant' is visible only to applicants; 'student' only to students.
     */
    public function scopeForAudience($query, string $audience)
    {
        return $query->where(function ($q) use ($audience) {
            $q->where('audience', self::AUDIENCE_BOTH)
                ->orWhere('audience', $audience);
        });
    }

    /**
     * Whether this row should be visible to a viewer of the given audience.
     */
    public function isVisibleTo(string $audience): bool
    {
        return $this->audience === self::AUDIENCE_BOTH
            || $this->audience === $audience;
    }
}
