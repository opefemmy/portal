<?php

namespace App\Models\Finance;

use App\Models\User;
use App\Models\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'transaction_number', 'user_id', 'session_id', 'type', 'category', 'ledger_code',
        'description', 'amount', 'balance', 'reference_type', 'reference_id', 'transaction_date',
        'status', 'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function isCredit(): bool
    {
        return $this->type === 'credit';
    }

    public function isDebit(): bool
    {
        return $this->type === 'debit';
    }

    public static function generateTransactionNumber(): string
    {
        $year = date('Y');
        $lastTransaction = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastTransaction ? (int)substr($lastTransaction->transaction_number, -6) + 1 : 1;
        return 'TXN-' . $year . '-' . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}