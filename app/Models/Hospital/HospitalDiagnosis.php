<?php

namespace App\Models\Hospital;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalDiagnosis extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'medical_record_id', 'patient_id', 'icd_code', 'diagnosis', 'description', 'severity', 'type'
    ];

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(HospitalMedicalRecord::class, 'medical_record_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(HospitalPatient::class, 'patient_id');
    }
}