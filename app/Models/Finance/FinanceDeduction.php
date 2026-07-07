<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceDeduction extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'amount', 'type', 'calculation_base', 'is_active', 'description'];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function staffDeductions(): HasMany
    {
        return $this->hasMany(FinanceStaffDeduction::class, 'deduction_id');
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