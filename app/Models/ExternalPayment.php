<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalPayment extends Model
{
    protected $fillable = [
        'transaction_id',
        'applicant_name',
        'email',
        'amount',
        'payment_date',
        'payment_status',
        'payment_channel',
        'description',
        'applicant_id',
        'is_used',
        'imported_by',
        'validated_by',
        'validated_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'validated_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Mark payment as used by an applicant
     */
    public function markAsUsed(int $applicantId, int $validatedBy): void
    {
        $this->update([
            'is_used' => true,
            'applicant_id' => $applicantId,
            'validated_by' => $validatedBy,
            'validated_at' => now(),
        ]);
    }

    /**
     * Check if transaction ID is valid (exists and not used)
     */
    public static function isValidTransaction(string $transactionId): bool
    {
        $payment = self::where('transaction_id', $transactionId)
            ->where('is_used', false)
            ->where('payment_status', 'completed')
            ->first();

        return $payment !== null;
    }

    /**
     * Get valid payment by transaction ID
     */
    public static function getValidPayment(string $transactionId): ?self
    {
        return self::where('transaction_id', $transactionId)
            ->where('is_used', false)
            ->where('payment_status', 'completed')
            ->first();
    }

    /**
     * Scope for unused payments
     */
    public function scopeUnused($query)
    {
        return $query->where('is_used', false);
    }

    /**
     * Scope for used payments
     */
    public function scopeUsed($query)
    {
        return $query->where('is_used', true);
    }

    /**
     * Scope for completed payments
     */
    public function scopeCompleted($query)
    {
        return $query->where('payment_status', 'completed');
    }
}
