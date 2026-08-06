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
    // NOTE: the production MySQL ENUM uses 'school_fees' (with
    // trailing s) but the historical codebase constant used the
    // shorter 'school_fee' (no s). Both spellings are accepted on
    // input by the alias map in the controller, and the legacy
    // constant is preserved here so existing call sites that read
    // PaymentType::PURPOSE_SCHOOL_FEE keep working unchanged.
    const PURPOSE_SCHOOL_FEE = 'school_fee';
    const PURPOSE_SCHOOL_FEE_PRODUCTION = 'school_fees';
    const PURPOSE_ACCEPTANCE = 'acceptance';
    const PURPOSE_HOSTEL = 'hostel';
    const PURPOSE_REGISTRATION = 'registration';
    const PURPOSE_LIBRARY = 'library';
    const PURPOSE_COMPULSORY = 'compulsory';
    const PURPOSE_OTHER = 'other';

    public static function getPurposes(): array
    {
        return [
            self::PURPOSE_APPLICATION => 'Application',
            self::PURPOSE_SCHOOL_FEE => 'School Fees',
            self::PURPOSE_ACCEPTANCE => 'Acceptance',
            self::PURPOSE_HOSTEL => 'Hostel',
            self::PURPOSE_REGISTRATION => 'Registration',
            self::PURPOSE_LIBRARY => 'Library',
            self::PURPOSE_COMPULSORY => 'Compulsory',
            self::PURPOSE_OTHER => 'Other',
        ];
    }

    /**
     * Friendly label for views. Falls back to title-cased purpose for
     * admin-defined purposes that aren't in the canonical map (e.g.
     * "late_registration" -> "Late Registration"). This is the single
     * source of truth for what to display on buttons, headings, etc.
     */
    public function getDisplayLabelAttribute(): string
    {
        $purposes = self::getPurposes();
        if (isset($purposes[$this->purpose])) {
            return $purposes[$this->purpose];
        }

        // Unknown purpose — title-case it for display.
        return ucwords(str_replace(['_', '-'], ' ', (string) $this->purpose));
    }

    /**
     * Find an active PaymentType by its `code` (e.g. 'APP_FORM'). Returns
     * null when not found or inactive. Case-insensitive on the lookup so
     * 'app_form' and 'APP_FORM' both match.
     */
    public static function findByCode(string $code): ?self
    {
        return static::whereRaw('LOWER(code) = ?', [strtolower($code)])->first();
    }

    /**
     * Find an active PaymentType by its `purpose`. If multiple rows share
     * the same purpose (admins sometimes create variations) the
     * lowest-priority / earliest-id one wins. Returns null when nothing
     * matches.
     */
    public static function findByPurpose(string $purpose): ?self
    {
        return static::where('purpose', $purpose)
            ->orderBy('priority')
            ->orderBy('id')
            ->first();
    }

    /**
     * Exact allow-list that matches the production MySQL ENUM
     * (verified via `php artisan payment-types:list-purposes`).
     * Anything outside this list must be coerced to one of these
     * values before INSERT or MySQL strict mode aborts the
     * transaction with a 1265 truncation error.
     *
     * NOTE: production's ENUM uses `school_fees` (with trailing s)
     * while the historical codebase constant uses `school_fee`. The
     * list below uses the production spelling — the controller
     * alias-maps the legacy spelling to the live value before
     * hitting this list.
     *
     * This method is the single source of truth for "what values
     * may be written to payment_types.purpose" — call it instead
     * of hard-coding purposes anywhere else.
     */
    public static function allowedPurposes(): array
    {
        return [
            self::PURPOSE_APPLICATION,
            self::PURPOSE_ACCEPTANCE,
            self::PURPOSE_SCHOOL_FEE_PRODUCTION,
            self::PURPOSE_HOSTEL,
            self::PURPOSE_REGISTRATION,
            self::PURPOSE_LIBRARY,
            self::PURPOSE_COMPULSORY,
            self::PURPOSE_OTHER,
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
