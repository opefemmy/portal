<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'student_id',
        'fee_id',
        'amount',
        'percent_paid',
        'installment_label',
        'reference',
        'payment_ref',
        'transaction_id',
        'transaction_ref',
        'gateway',
        'payment_method',
        'status',
        'payment_details',
        'portal_charge',
        'total_amount',
        'payment_date',
        'paid_at',
        'payer_name',
        'payer_email',
        'payer_phone',
        'payer_id',
        'payment_purpose',
        'installment',
        'student_type',
        'is_verified',
        'fee_type',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'portal_charge' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'percent_paid' => 'integer',
        'is_verified' => 'boolean',
        'payment_date' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class);
    }

    /**
     * The polymorphic fee-type relation. The legacy `fee()` relation
     * points at the student-side `Fee` catalogue (school fees, library,
     * hostel). Applicant-side payments — created before the applicant is
     * migrated to a Student row — write `fee_id` pointing at the
     * `PaymentType` catalogue instead (application, acceptance, compulsory).
     *
     * The two relations coexist on the same `fee_id` column because Eloquent
     * only resolves them when accessed. Views that need to render either
     * row type fall back through `fee` → `paymentType` → `payment_purpose`.
     *
     * Added so `/student/payments` can render the application / acceptance
     * / compulsory fee rows that the migration back-fill links to the new
     * student (ApplicantPaymentService::migrateApplicantToStudent).
     */
    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class, 'fee_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class, 'payer_id');
    }

    public static function generateReference()
    {
        return 'PAY-' . strtoupper(uniqid()) . '-' . date('Ymd');
    }

    /* ------------------------------------------------------------------
     | Status constants + scopes (single source of truth)
     |
     | The payments.status column is varchar(20); the portal writes
     | 'pending', 'completed', 'verified', 'failed' and 'cancelled'
     | across the three payment flows (applicant / student / hospital
     | pre-paid). Constants keep the magic strings out of the controllers,
     | scopes power the new "retry pending attempt" behaviour —
     | `scopeRetryable` returns rows whose gateway callback never confirmed
     | success, so we can reuse the same row instead of creating a
     | duplicate payment when the payer hits Pay twice on a stuck attempt.
     * ------------------------------------------------------------------*/

    public const STATUS_PENDING   = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_VERIFIED  = 'verified';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    /** Statuses that block a NEW payment attempt because the fee is already paid. */
    public const PAID_STATUSES = [self::STATUS_COMPLETED, self::STATUS_VERIFIED];

    /** Statuses of an unfinished attempt the payer is allowed to retry. */
    public const RETRYABLE_STATUSES = [self::STATUS_PENDING, self::STATUS_FAILED];

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /** Pending OR failed — gateway never confirmed success; safe to retry. */
    public function scopeRetryable($query)
    {
        return $query->whereIn('status', self::RETRYABLE_STATUSES);
    }

    /** Most recent open attempt for a specific applicant + purpose. */
    public function scopeOpenForPayer($query, int $payerId, string $purpose)
    {
        return $query->where('payer_id', $payerId)
            ->where('payment_purpose', $purpose)
            ->retryable()
            ->latest('created_at');
    }

    /** Most recent open attempt for a specific student + fee. */
    public function scopeOpenForStudent($query, int $studentId, int $feeId)
    {
        return $query->where('student_id', $studentId)
            ->where('fee_id', $feeId)
            ->retryable()
            ->latest('created_at');
    }

    /**
     * True if this attempt is still open — the payer can hit "Pay" /
     * "Retry" again and we'll reuse the row instead of inserting another.
     */
    public function isRetryable(): bool
    {
        return in_array($this->status, self::RETRYABLE_STATUSES, true);
    }

    /**
     * Refresh the mutable gateway-side fields so a second click on
     * "Pay" produces a fresh reference. Leaves the row id, payer_id,
     * payment_purpose, fee_id intact so the schema history is one row,
     * not several. Caller should wrap in a transaction when followed by
     * a PaymentGateway create call.
     */
    public function refreshForRetry(string $reference, string $gateway): void
    {
        $this->update([
            'reference'      => $reference,
            'payment_ref'    => $reference,
            'transaction_id' => $reference,
            'gateway'        => $gateway,
            'status'         => self::STATUS_PENDING,
            'payment_date'   => null,
            'paid_at'        => null,
            'is_verified'    => false,
        ]);
    }
}