<?php

namespace App\Models\Finance;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceBudget extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'fiscal_year', 'department_id', 'total_budget', 'total_spent', 'balance',
        'start_date', 'end_date', 'status', 'approved_by', 'approved_at', 'notes'
    ];

    protected $casts = [
        'total_budget' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'balance' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(FinanceBudgetAllocation::class, 'budget_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->end_date->isPast();
    }

    public function getPercentageSpentAttribute(): float
    {
        if ($this->total_budget == 0) return 0;
        return round(($this->total_spent / $this->total_budget) * 100, 2);
    }
}