<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fee extends Model
{
    protected $fillable = ['name', 'payment_type', 'amount', 'school_id', 'department_id', 'programme_id', 'level', 'session_id', 'due_date', 'is_active', 'category'];

    protected $casts = [
        'due_date' => 'date',
        'is_active' => 'boolean',
        'amount' => 'decimal:2',
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
}