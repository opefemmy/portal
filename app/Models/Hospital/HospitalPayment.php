<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalPayment extends Model
{
    protected $table = 'hospital_payments';

    // Valid status values
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'payment_ref',
        'patient_name',
        'patient_email',
        'patient_phone',
        'patient_gender',
        'patient_age',
        'service_type_id',
        'service_name',
        'amount',
        'portal_charge',
        'total_amount',
        'payment_method',
        'status',
        'payment_date',
        'appointment_date',
        'doctor_name',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'portal_charge' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'appointment_date' => 'datetime',
        'payment_date' => 'date',
    ];

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(HospitalServiceType::class, 'service_type_id');
    }

    /**
     * Scope for pending payments
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for submitted payments
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    /**
     * Scope for completed payments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Check if payment can be submitted
     */
    public function canSubmit(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING]);
    }

    /**
     * Submit payment (change status from pending to submitted)
     */
    public function submit(): bool
    {
        if (!$this->canSubmit()) {
            return false;
        }

        return $this->update(['status' => self::STATUS_SUBMITTED]);
    }

    /**
     * Mark payment as completed
     */
    public function markAsCompleted(): bool
    {
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_SUBMITTED])) {
            return false;
        }

        return $this->update(['status' => self::STATUS_COMPLETED]);
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(): bool
    {
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_SUBMITTED])) {
            return false;
        }

        return $this->update(['status' => self::STATUS_FAILED]);
    }

    /**
     * Check if payment is paid/completed
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
