<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;

class HospitalPayment extends Model
{
    protected $table = 'hospital_payments';

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

    public function serviceType()
    {
        return $this->belongsTo(HospitalServiceType::class, 'service_type_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
