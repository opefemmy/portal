<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['student_id', 'fee_id', 'amount', 'reference', 'transaction_id', 'gateway', 'status', 'payment_details', 'installment', 'student_type', 'is_verified', 'fee_type'];

    protected $casts = [
        'amount' => 'decimal:2',
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

    public static function generateReference()
    {
        return 'PAY-' . strtoupper(uniqid()) . '-' . date('Ymd');
    }
}