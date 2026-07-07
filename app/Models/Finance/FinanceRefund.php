<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceRefund extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'refund_number', 'student_id', 'receipt_id', 'requested_by', 'approved_by',
        'amount', 'reason', 'status', 'payment_method', 'reference_number',
        'approved_at', 'processed_at', 'rejection_reason'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(FinanceReceipt::class, 'receipt_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public static function generateRefundNumber(): string
    {
        $year = date('Y');
        $lastRefund = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastRefund ? (int)substr($lastRefund->refund_number, -5) + 1 : 1;
        return 'REF-' . $year . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}