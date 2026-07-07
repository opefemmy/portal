<?php

namespace App\Models\Finance;

use App\Models\User;
use App\Models\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceInvoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number', 'student_id', 'generated_by', 'session_id', 'payment_type',
        'description', 'amount', 'amount_paid', 'balance', 'discount', 'penalty',
        'status', 'due_date', 'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'discount' => 'decimal:2',
        'penalty' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(FinanceReceipt::class, 'invoice_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'paid';
    }

    public static function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $lastInvoice = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastInvoice ? (int)substr($lastInvoice->invoice_number, -5) + 1 : 1;
        return 'INV-' . $year . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}