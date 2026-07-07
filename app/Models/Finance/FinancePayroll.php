<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancePayroll extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'staff_id', 'month', 'year', 'basic_salary', 'total_allowances', 'total_deductions',
        'gross_salary', 'net_salary', 'tax_deducted', 'pension_deducted', 'status', 'processed_by', 'processed_at'
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'tax_deducted' => 'decimal:2',
        'pension_deducted' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function allowances(): HasMany
    {
        return $this->hasMany(FinanceStaffAllowance::class, 'payroll_id');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(FinanceStaffDeduction::class, 'payroll_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function getPeriodAttribute(): string
    {
        return $this->month . ' ' . $this->year;
    }
}