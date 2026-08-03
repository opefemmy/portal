<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Applicant extends Model
{
    protected $fillable = [
        // Personal Information
        'user_id', 'application_number', 'surname', 'first_name', 'middle_name',
        'date_of_birth', 'place_of_birth', 'gender', 'marital_status',
        'nationality', 'state_of_origin', 'lga', 'permanent_address',
        'contact_address', 'email', 'phone', 'passport',
        'religion', 'blood_group', 'genotype', 'disability', 'disability_details',
        'address', 'state_id', 'nationality_id',

        // Guardian Information
        'guardian_name', 'guardian_relationship', 'guardian_phone',
        'guardian_email', 'guardian_occupation', 'guardian_address',

        // Educational Background
        'primary_school', 'primary_school_start', 'primary_school_end',
        'secondary_school', 'secondary_school_start', 'secondary_school_end',
        'tertiary_institution', 'tertiary_qualification', 'tertiary_start', 'tertiary_end',

        // Programme Selection
        'school_id', 'department_id', 'programme_id', 'session_id', 'centre_id',
        'mode_of_study', 'entry_level',

        // JAMB Details
        'jamb_registration_number', 'jamb_year', 'jamb_score',
        'jamb_subject1', 'jamb_subject2', 'jamb_subject3', 'jamb_subject4',

        // O-Level Results
        'olevel1_subject1', 'olevel1_grade1', 'olevel1_subject2', 'olevel1_grade2',
        'olevel1_subject3', 'olevel1_grade3', 'olevel1_subject4', 'olevel1_grade4',
        'olevel1_subject5', 'olevel1_grade5', 'olevel1_exam_year',
        'olevel1_exam_type', 'olevel1_exam_number',
        'olevel2_subject1', 'olevel2_grade1', 'olevel2_subject2', 'olevel2_grade2',
        'olevel2_subject3', 'olevel2_grade3', 'olevel2_subject4', 'olevel2_grade4',
        'olevel2_subject5', 'olevel2_grade5', 'olevel2_exam_year',
        'olevel2_exam_type', 'olevel2_exam_number',

        // Extra Curricular
        'extra_curricular',

        // Documents
        'olevel_certificate', 'tertiary_certificate', 'birth_certificate',
        'lga_id', 'jamb_result',

        // Payment
        'payment_status', 'payment_ref', 'payment_transaction_id',
        'payment_amount', 'payment_date', 'application_fee_id',

        // Per-purpose payment timestamps (filled by ApplicantPaymentService)
        'application_paid_at', 'acceptance_paid_at', 'compulsory_paid_at',
        'migrated_to_student_at',

        // Migration (filled when compulsory fee is paid and applicant becomes a Student)
        'student_id',

        // Status
        'status', 'rejection_reason', 'reviewed_by', 'reviewed_at', 'matric_number'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'jamb_score' => 'integer',
        'reviewed_at' => 'datetime',
        'application_paid_at' => 'datetime',
        'acceptance_paid_at' => 'datetime',
        'compulsory_paid_at' => 'datetime',
        'migrated_to_student_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get full name (from applicant or user)
     */
    public function getFullNameAttribute()
    {
        if ($this->surname || $this->first_name) {
            return trim(($this->surname ?? '') . ' ' . ($this->first_name ?? '') . ' ' . ($this->middle_name ?? ''));
        }
        return $this->user?->name ?? 'N/A';
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function centre(): BelongsTo
    {
        return $this->belongsTo(AdmissionCentre::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(LocalGovernment::class, 'lga_id');
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function payments(): HasMany
    {
        // Online Payment rows (Paystack/Flutterwave/Xpress) link to the applicant
        // via payments.payer_id. This was made nullable to support the new flow.
        return $this->hasMany(Payment::class, 'payer_id');
    }

    public function externalPayments(): HasMany
    {
        return $this->hasMany(ExternalPayment::class);
    }

    /* -----------------------------------------------------------------
     | Per-purpose payment state (used by gates, dashboard, history view)
     * -----------------------------------------------------------------*/

    /**
     * Whether the applicant has paid the given purpose.
     * purpose: application | acceptance | compulsory
     */
    public function hasPaid(string $purpose): bool
    {
        return match ($purpose) {
            PaymentType::PURPOSE_APPLICATION => ! is_null($this->application_paid_at),
            PaymentType::PURPOSE_ACCEPTANCE => ! is_null($this->acceptance_paid_at),
            PaymentType::PURPOSE_SCHOOL_FEE => ! is_null($this->compulsory_paid_at),
            default => false,
        };
    }

    /**
     * The next fee the applicant should pay, in canonical order.
     * Returns null once all three have been paid.
     */
    public function nextPayablePurpose(): ?string
    {
        if (! $this->hasPaid(PaymentType::PURPOSE_APPLICATION)) {
            return PaymentType::PURPOSE_APPLICATION;
        }

        // Acceptance only matters once the registrar has admitted the applicant.
        if ($this->status === 'admitted' && ! $this->hasPaid(PaymentType::PURPOSE_ACCEPTANCE)) {
            return PaymentType::PURPOSE_ACCEPTANCE;
        }

        if ($this->status === 'admitted'
            && $this->hasPaid(PaymentType::PURPOSE_ACCEPTANCE)
            && ! $this->hasPaid(PaymentType::PURPOSE_SCHOOL_FEE)) {
            return PaymentType::PURPOSE_SCHOOL_FEE;
        }

        return null;
    }

    /**
     * Human-friendly label for the next fee (used on the dashboard button).
     */
    public function nextPayableLabel(): ?string
    {
        $purpose = $this->nextPayablePurpose();

        return match ($purpose) {
            PaymentType::PURPOSE_APPLICATION => 'Pay Application Fee',
            PaymentType::PURPOSE_ACCEPTANCE => 'Pay Acceptance Fee',
            PaymentType::PURPOSE_SCHOOL_FEE => 'Pay Compulsory Fee',
            default => null,
        };
    }

    /**
     * Whether the applicant has been migrated into the Student table.
     */
    public function isMigrated(): bool
    {
        return ! is_null($this->student_id) || ! is_null($this->migrated_to_student_at);
    }

    /**
     * Unified transaction history.
     *
     * Combines online payments (`payments.payer_id = $this->id`) and bank
     * transfers (`external_payments.applicant_id = $this->id`) into a single
     * normalised shape, sorted by paid_at desc.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function transactionHistory(): Collection
    {
        $online = $this->payments()
            ->where('status', 'completed')
            ->get()
            ->map(function (Payment $p): array {
                return [
                    'reference' => $p->reference ?: $p->payment_ref ?: $p->transaction_id,
                    'amount' => (float) ($p->total_amount ?: $p->amount),
                    'purpose' => $p->payment_purpose ?: $p->fee_type,
                    'channel' => $p->payment_method ?: $p->gateway ?: 'online',
                    'status' => $p->status,
                    'paid_at' => $p->payment_date ?: $p->updated_at,
                    'payer_name' => $p->payer_name,
                    'payer_email' => $p->payer_email,
                    'source' => 'online',
                    'receipt_url' => $p->reference
                        ? route('online-payment.receipt', ['payment' => $p->reference], false)
                        : null,
                ];
            });

        $manual = $this->externalPayments()
            ->where('payment_status', 'completed')
            ->get()
            ->map(function (ExternalPayment $e): array {
                $purpose = $e->paymentType?->purpose ?: 'other';
                // External payments are validated by the applicant, so they may
                // carry any purpose. Try to resolve from description if missing.
                if ($purpose === 'other' && $e->description) {
                    $purpose = $this->guessPurposeFromDescription($e->description);
                }

                return [
                    'reference' => $e->transaction_id,
                    'amount' => (float) $e->amount,
                    'purpose' => $purpose,
                    'channel' => $e->payment_channel ?: 'bank_transfer',
                    'status' => $e->payment_status,
                    'paid_at' => $e->payment_date ?: $e->validated_at,
                    'payer_name' => $e->applicant_name,
                    'payer_email' => $e->email,
                    'source' => 'manual',
                    'receipt_url' => null,
                ];
            });

        return $online
            ->merge($manual)
            ->sortByDesc(fn (array $row) => $row['paid_at'] instanceof Carbon
                ? $row['paid_at']->timestamp
                : strtotime((string) $row['paid_at']))
            ->values();
    }

    /**
     * Fallback when an external payment has no linked PaymentType row —
     * match on the description string the cashier typed when uploading.
     */
    private function guessPurposeFromDescription(string $description): string
    {
        $d = strtolower($description);

        if (str_contains($d, 'application')) {
            return PaymentType::PURPOSE_APPLICATION;
        }
        if (str_contains($d, 'acceptance') || str_contains($d, 'accept')) {
            return PaymentType::PURPOSE_ACCEPTANCE;
        }
        if (str_contains($d, 'compulsory') || str_contains($d, 'school')) {
            return PaymentType::PURPOSE_SCHOOL_FEE;
        }

        return PaymentType::PURPOSE_OTHER;
    }

    public static function generateApplicationNumber()
    {
        return 'APP-' . strtoupper(Str::random(8));
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeScreening($query)
    {
        return $query->where('status', 'screening');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeAdmitted($query)
    {
        return $query->where('status', 'admitted');
    }

    /**
     * Determine if applicant is an indigene (from Ekiti state).
     * Delegated to IndigeneResolver so the keyword list stays in one place.
     */
    public function getCategoryAttribute(): string
    {
        return \App\Services\IndigeneResolver::categoryFor($this);
    }

    /**
     * Check if applicant is an indigene.
     */
    public function isIndigene(): bool
    {
        return \App\Services\IndigeneResolver::isIndigene($this);
    }
}