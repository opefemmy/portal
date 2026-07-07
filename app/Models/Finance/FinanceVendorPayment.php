<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceVendorPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_number', 'vendor_id', 'po_id', 'processed_by', 'amount', 'payment_method',
        'reference_number', 'cheque_number', 'payment_date', 'status', 'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(FinanceVendor::class, 'vendor_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(FinancePurchaseOrder::class, 'po_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public static function generatePaymentNumber(): string
    {
        $year = date('Y');
        $lastPayment = self::whereYear('created_at', $year)->orderBy('id', 'desc')->first();
        $number = $lastPayment ? (int)substr($lastPayment->payment_number, -5) + 1 : 1;
        return 'VP-' . $year . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}