<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalMedicalRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'doctor_id', 'appointment_id', 'chief_complaint', 'symptoms',
        'examination_findings', 'doctor_notes', 'treatment_plan', 'consultation_date', 'visit_type'
    ];

    protected $casts = [
        'consultation_date' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(HospitalPatient::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(HospitalStaff::class, 'doctor_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(HospitalAppointment::class, 'appointment_id');
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(HospitalDiagnosis::class, 'medical_record_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(HospitalPrescription::class, 'medical_record_id');
    }

    public function labRequests(): HasMany
    {
        return $this->hasMany(HospitalLabRequest::class, 'medical_record_id');
    }
}