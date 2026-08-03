<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fee extends Model
{
    protected $fillable = [
        'name',
        'payment_type',
        'amount',
        'indigene_amount',
        'non_indigene_amount',
        'portal_charge',
        'portal_charge_percentage',
        'is_editable_amount',
        'school_id',
        'department_id',
        'programme_id',
        'level',
        'session_id',
        'due_date',
        'is_active',
        'category',
    ];

    protected $casts = [
        'due_date' => 'date',
        'is_active' => 'boolean',
        'amount' => 'decimal:2',
        'indigene_amount' => 'decimal:2',
        'non_indigene_amount' => 'decimal:2',
        'portal_charge' => 'decimal:2',
        'portal_charge_percentage' => 'decimal:2',
        'is_editable_amount' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getLevelDisplayAttribute(): string
    {
        if (!$this->level) return 'All Levels';
        return match($this->level) {
            1 => '100L / ND1',
            2 => '200L / ND',
            3 => '300L / HND1',
            4 => '400L / HND2',
            5 => '500L',
            6 => '600L',
            default => (string) $this->level,
        };
    }

    /**
     * Resolve the price this fee charges a student of the given category
     * ('indigene' or 'non_indigene'). When the per-category column is null
     * we fall back to the legacy `amount` column.
     */
    public function priceFor(string $category): float
    {
        $col = $category === 'indigene' ? 'indigene_amount' : 'non_indigene_amount';
        $value = $this->{$col};
        return $value !== null ? (float) $value : (float) $this->amount;
    }

    /**
     * Total the student has to pay for a single full payment of this fee
     * (price + portal charge). Portal charge is only added on a 100% payment,
     * not on partial installments — see SchoolFeeCalculator.
     */
    public function totalPayable(string $category): float
    {
        return $this->priceFor($category) + (float) $this->portal_charge;
    }
}