<?php

namespace App\Models\Hospital;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * HospitalOrderItem
 *
 * Polymorphic linking row that connects a doctor's prescribed item
 * (prescription item or lab request) to a payment. The doctor creates
 * the order, the patient sees it on their dashboard and pays, and the
 * pharmacy / lab is then allowed to fulfil it.
 */
class HospitalOrderItem extends Model
{
    protected $table = 'hospital_order_items';

    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'orderable_type',
        'orderable_id',
        'patient_id',
        'external_patient_id',
        'item_name',
        'amount',
        'status',
        'payment_id',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function orderable(): MorphTo
    {
        return $this->morphTo();
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(HospitalPatient::class, 'patient_id');
    }

    public function externalPatient(): BelongsTo
    {
        return $this->belongsTo(ExternalPatient::class, 'external_patient_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(HospitalPayment::class, 'payment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeAwaitingPayment($query)
    {
        return $query->where('status', self::STATUS_AWAITING_PAYMENT);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }
}