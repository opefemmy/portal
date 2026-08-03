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
        'gateway',
        'payment_method',
        'status',
        'payment_details',
        'portal_charge',
        'total_amount',
        'payment_date',
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
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class);
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