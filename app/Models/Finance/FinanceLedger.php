<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceLedger extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'name', 'type', 'category', 'parent_id', 'opening_balance', 'balance',
        'is_active', 'allow_manual_entry'
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
        'allow_manual_entry' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(FinanceLedger::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(FinanceLedger::class, 'parent_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'ledger_code', 'code');
    }

    public function isDebitType(): bool
    {
        return in_array($this->type, ['asset', 'expense']);
    }

    public function isCreditType(): bool
    {
        return in_array($this->type, ['liability', 'income']);
    }
}