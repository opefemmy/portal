<?php

namespace App\Models\Finance;

use App\Models\User;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceReceipt extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'receipt_number', 'invoice_id', 'student_id', 'payment_id', 'generated_by',
        'amount', 'amount_received', 'change_given', 'payment_method', 'reference_number',
        'bank_name', 'cheque_number', 'payment_date', 'notes', 'is_verified', 'verified_by', 'verified_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_received' => 'decimal:2',
        'change_given' => 'decimal:2',
        'payment_date' => 'date',
        'verified_at' => 'datetime',
        'is_verified' => 'boolean',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(FinanceInvoice::class, 'invoice_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public static function generateReceiptNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $lastReceipt = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastReceipt ? (int)substr($lastReceipt->receipt_number, -6) + 1 : 1;
        return 'RCP-' . $year . $month . '-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}