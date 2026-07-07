<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceCashBook extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'entry_number', 'user_id', 'type', 'date', 'description', 'cash_in', 'cash_out',
        'balance', 'reference_type', 'reference_id', 'notes'
    ];

    protected $casts = [
        'cash_in' => 'decimal:2',
        'cash_out' => 'decimal:2',
        'balance' => 'decimal:2',
        'date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isReceipt(): bool
    {
        return $this->type === 'receipt';
    }

    public function isPayment(): bool
    {
        return $this->type === 'payment';
    }

    public static function generateEntryNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $lastEntry = self::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $number = $lastEntry ? (int)substr($lastEntry->entry_number, -5) + 1 : 1;
        return 'CB-' . $year . $month . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
}