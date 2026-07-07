<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalPrescription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'doctor_id', 'medical_record_id', 'notes', 'status', 'dispensed_by', 'dispensed_at'
    ];

    protected $casts = [
        'dispensed_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(HospitalPatient::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(HospitalStaff::class, 'doctor_id');
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(HospitalMedicalRecord::class, 'medical_record_id');
    }

    public function dispensedBy(): BelongsTo
    {
        return $this->belongsTo(HospitalStaff::class, 'dispensed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(HospitalPrescriptionItem::class, 'prescription_id');
    }
}