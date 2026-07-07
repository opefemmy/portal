<?php

namespace App\Models\Finance;

use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceDepartmentLedger extends Model
{
    protected $table = 'finance_department_ledgers';

    protected $fillable = [
        'department_id',
        'ledger_id',
        'allocation',
        'spent',
        'balance',
        'fiscal_year_id',
    ];

    protected $casts = [
        'allocation' => 'decimal:2',
        'spent' => 'decimal:2',
        'balance' => 'decimal:2',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function ledger(): BelongsTo
    {
        return $this->belongsTo(FinanceLedger::class, 'ledger_id');
    }
}