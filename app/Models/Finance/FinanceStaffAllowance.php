<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceStaffAllowance extends Model
{
    public $timestamps = false;

    protected $fillable = ['payroll_id', 'allowance_id', 'amount'];

    protected $casts = ['amount' => 'decimal:2'];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(FinancePayroll::class, 'payroll_id');
    }

    public function allowance(): BelongsTo
    {
        return $this->belongsTo(FinanceAllowance::class, 'allowance_id');
    }
}