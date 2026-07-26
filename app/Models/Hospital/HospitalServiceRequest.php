<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalServiceRequest extends Model
{
    protected $table = 'hospital_service_requests';

    protected $fillable = [
        'patient_id',
        'service_type_id',
        'request_code',
        'service_name',
        'category',
        'amount',
        'portal_charge',
        'total_amount',
        'appointment_date',
        'notes',
        'status',
        'payment_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'portal_charge' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'appointment_date' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ExternalPatient::class, 'patient_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(HospitalServiceType::class, 'service_type_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(HospitalPayment::class, 'payment_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
