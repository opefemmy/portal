<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceBudgetAllocation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'budget_id', 'ledger_id', 'allocated_amount', 'spent_amount', 'balance'
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(FinanceBudget::class, 'budget_id');
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(FinanceLedger::class, 'ledger_id');
    }

    public function getPercentageSpentAttribute(): float
    {
        if ($this->allocated_amount == 0) return 0;
        return round(($this->spent_amount / $this->allocated_amount) * 100, 2);
    }
}