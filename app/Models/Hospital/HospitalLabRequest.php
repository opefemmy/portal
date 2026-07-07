<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalLabRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'patient_id', 'doctor_id', 'medical_record_id', 'test_type', 'clinical_notes',
        'status', 'requested_at', 'completed_at', 'amount'
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'completed_at' => 'datetime',
        'amount' => 'decimal:2',
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

    public function results(): HasMany
    {
        return $this->hasMany(HospitalLabResult::class, 'lab_request_id');
    }
}