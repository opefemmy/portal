<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceAllowance extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'amount', 'type', 'is_taxable', 'is_active', 'description'];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_taxable' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function staffAllowances(): HasMany
    {
        return $this->hasMany(FinanceStaffAllowance::class, 'allowance_id');
    }

    public function isFixed(): bool
    {
        return $this->type === 'fixed';
    }

    public function isPercentage(): bool
    {
        return $this->type === 'percentage';
    }
}