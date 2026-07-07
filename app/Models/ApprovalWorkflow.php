<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalWorkflow extends Model
{
    protected $fillable = [
        'name', 'module', 'approval_type', 'level', 'approver_role_id', 'min_amount', 'max_amount', 'is_active'
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function approverRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'approver_role_id');
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function amountQualifies(decimal $amount): bool
    {
        if ($this->min_amount !== null && $amount < $this->min_amount) {
            return false;
        }
        if ($this->max_amount !== null && $amount > $this->max_amount) {
            return false;
        }
        return true;
    }
}