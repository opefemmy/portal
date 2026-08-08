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
}