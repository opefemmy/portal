<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceStaffDeduction extends Model
{
    public $timestamps = false;

    protected $fillable = ['payroll_id', 'deduction_id', 'amount'];

    protected $casts = ['amount' => 'decimal:2'];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(FinancePayroll::class, 'payroll_id');
    }

    public function deduction(): BelongsTo
    {
        return $this->belongsTo(FinanceDeduction::class, 'deduction_id');
    }
}